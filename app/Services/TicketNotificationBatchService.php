<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketNotificationBatch;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TicketNotificationBatchService
{
    private const BATCHABLE_EVENTS = [
        'assignee_changed',
        'status_changed',
        'ticket_updated',
        'public_comment',
        'expected_resolution_changed',
        'resolved',
        'closed',
    ];

    public function enabled(): bool
    {
        return (bool) config('helpdesk.notifications.mail.batch.enabled', true);
    }

    public function shouldBatch(Ticket $ticket, string $event, ?User $actor, User $recipient): bool
    {
        return $this->enabled()
            && $actor instanceof User
            && $actor->isSolver()
            && (int) $recipient->id === (int) $ticket->requester_id
            && (int) $recipient->id !== (int) $actor->id
            && in_array($event, self::BATCHABLE_EVENTS, true);
    }

    public function shouldFlushImmediately(string $event): bool
    {
        return $event === 'problem_persists';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function add(
        Ticket $ticket,
        User $recipient,
        string $event,
        ?User $actor,
        array $context = [],
    ): TicketNotificationBatch {
        try {
            return $this->storeItem($ticket, $recipient, $event, $actor, $context);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            // A concurrent request created the active ticket-recipient batch first.
            return $this->storeItem($ticket, $recipient, $event, $actor, $context);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function storeItem(
        Ticket $ticket,
        User $recipient,
        string $event,
        ?User $actor,
        array $context,
    ): TicketNotificationBatch {
        return DB::transaction(function () use ($ticket, $recipient, $event, $actor, $context): TicketNotificationBatch {
            $now = CarbonImmutable::now();
            $batch = TicketNotificationBatch::query()
                ->where('ticket_id', $ticket->id)
                ->where('recipient_id', $recipient->id)
                ->where('active_marker', true)
                ->lockForUpdate()
                ->first();

            if (! $batch instanceof TicketNotificationBatch) {
                $batch = TicketNotificationBatch::query()->create([
                    'ticket_id' => $ticket->id,
                    'recipient_id' => $recipient->id,
                    'first_event_at' => $now,
                    'last_event_at' => $now,
                    'send_after' => $this->sendAfter($now, $now),
                    'status' => TicketNotificationBatch::STATUS_PENDING,
                    'active_marker' => true,
                ]);
            }

            $firstEventAt = CarbonImmutable::instance($batch->first_event_at);
            $previousSendAfter = CarbonImmutable::instance($batch->send_after);
            $activatesActionGrace = $this->activatesActionGrace($ticket, $event);
            $actionGraceUntil = $this->actionGraceUntil($ticket, $event, $batch->action_grace_until, $now);

            $itemContext = $this->itemContext($ticket, $actor, $context);
            $activityId = $itemContext['ticket_activity_id'] ?? null;
            unset($itemContext['ticket_activity_id']);

            $batch->items()->create([
                'event' => $event,
                'actor_id' => $actor?->id,
                'ticket_activity_id' => is_numeric($activityId) ? (int) $activityId : null,
                'context' => $itemContext,
                'created_at' => $now,
            ]);

            $sendAfter = $this->sendAfter($firstEventAt, $now, $actionGraceUntil);

            if ($activatesActionGrace && $previousSendAfter->lessThan($sendAfter)) {
                $sendAfter = $previousSendAfter;
            }

            $batch->forceFill([
                'last_event_at' => $now,
                'action_grace_until' => $actionGraceUntil,
                'send_after' => $sendAfter,
                'status' => TicketNotificationBatch::STATUS_PENDING,
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            return $batch->refresh();
        }, 3);
    }

    private function sendAfter(
        CarbonImmutable $firstEventAt,
        CarbonImmutable $lastEventAt,
        ?CarbonImmutable $actionGraceUntil = null,
    ): CarbonImmutable
    {
        $quietDeadline = $lastEventAt->addMinutes($this->quietMinutes());
        $maximumDeadline = $firstEventAt->addMinutes($this->maximumMinutes());
        $sendAfter = $quietDeadline->lessThan($maximumDeadline) ? $quietDeadline : $maximumDeadline;

        return $actionGraceUntil instanceof CarbonImmutable && $actionGraceUntil->lessThan($sendAfter)
            ? $actionGraceUntil
            : $sendAfter;
    }

    private function actionGraceUntil(
        Ticket $ticket,
        string $event,
        ?DateTimeInterface $currentGraceUntil,
        CarbonImmutable $now,
    ): ?CarbonImmutable
    {
        $current = $currentGraceUntil !== null
            ? CarbonImmutable::instance($currentGraceUntil)
            : null;

        if (! $this->activatesActionGrace($ticket, $event)) {
            return $current;
        }

        $candidate = $now->addMinutes($this->actionGraceMinutes());

        return $current instanceof CarbonImmutable && $current->lessThan($candidate)
            ? $current
            : $candidate;
    }

    private function activatesActionGrace(Ticket $ticket, string $event): bool
    {
        return in_array($event, ['resolved', 'closed'], true)
            || ($event === 'status_changed' && $ticket->hasStatusSlug('waiting_user'));
    }

    private function quietMinutes(): int
    {
        return min(60, max(1, (int) config('helpdesk.notifications.mail.batch.quiet_minutes', 10)));
    }

    private function maximumMinutes(): int
    {
        $configured = min(240, max(5, (int) config('helpdesk.notifications.mail.batch.max_minutes', 30)));

        return max($this->quietMinutes(), $configured);
    }

    private function actionGraceMinutes(): int
    {
        return min(15, max(1, (int) config('helpdesk.notifications.mail.batch.action_grace_minutes', 3)));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function itemContext(Ticket $ticket, ?User $actor, array $context): array
    {
        $ticket->loadMissing(['status:id,name,slug', 'assignee:id,name,display_name,username']);

        return [
            ...$context,
            'actor_name' => $context['actor_name'] ?? $actor?->notificationName(),
            'status_slug' => $ticket->status?->slug,
            'status_name' => $ticket->status?->name,
            'assignee' => $context['assignee'] ?? $ticket->assignee?->displayName(),
        ];
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23000', '19'], true);
    }
}
