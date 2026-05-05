<?php

namespace Tests\Feature;

use App\Models\IncomingEmail;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\InboundAttachmentRejectedNotifier;
use App\Services\InboundEmailReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class InboundEmailReplyTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private TicketStatus $defaultStatus;

    private TicketPriority $defaultPriority;

    private TicketCategory $defaultCategory;

    private FakeInboundAttachmentRejectedNotifier $fakeNotifier;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('helpdesk.inbound.import_attachments', false);
        config()->set('helpdesk.inbound.notify_rejected_attachments', true);

        $this->fakeNotifier = new FakeInboundAttachmentRejectedNotifier();
        $this->app->instance(InboundAttachmentRejectedNotifier::class, $this->fakeNotifier);

        $this->userRole = Role::query()->create([
            'name' => 'User',
            'slug' => Role::SLUG_USER,
            'is_system' => true,
        ]);

        $this->defaultStatus = TicketStatus::query()->create([
            'name' => 'New',
            'slug' => 'new',
            'sort_order' => 1,
            'is_default' => true,
        ]);

        $this->defaultPriority = TicketPriority::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'sort_order' => 1,
            'is_default' => true,
        ]);

        $this->defaultCategory = TicketCategory::query()->create([
            'name' => 'Obecné',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }

    public function test_inbound_reply_with_text_and_attachment_creates_comment_with_ignored_attachment_note(): void
    {
        $sender = $this->createUser();
        $ticket = $this->createTicket($sender);

        $comment = $this->service()->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: 'requester@example.org',
            messageId: '<message-with-attachment@example.org>',
            cleanBody: 'Here is my reply.',
            attachments: [$this->realAttachment()],
        );

        $this->assertSame('public', $comment->visibility);
        $this->assertStringContainsString('Here is my reply.', $comment->body);
        $this->assertStringContainsString(
            __('notifications.inbound.attachments_ignored.comment_note', [], $sender->preferred_locale ?: app()->getLocale()),
            $comment->body,
        );
    }

    public function test_attachment_rejection_notice_is_sent_to_sender_when_enabled(): void
    {
        $sender = $this->createUser(['preferred_locale' => 'en']);
        $ticket = $this->createTicket($sender);

        $this->service()->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: 'requester@example.org',
            messageId: '<notice-enabled@example.org>',
            cleanBody: 'Reply text.',
            attachments: [$this->realAttachment()],
        );

        $this->assertSame('requester@example.org', $this->fakeNotifier->sent[0]['email'] ?? null);
        $this->assertSame('en', $this->fakeNotifier->sent[0]['locale'] ?? null);

        $this->assertNotNull(IncomingEmail::query()
            ->where('message_id', '<notice-enabled@example.org>')
            ->value('attachment_notice_sent_at'));
    }

    public function test_attachment_rejection_notice_is_not_sent_when_disabled(): void
    {
        config()->set('helpdesk.inbound.notify_rejected_attachments', false);
        $sender = $this->createUser();
        $ticket = $this->createTicket($sender);

        $comment = $this->service()->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: 'requester@example.org',
            messageId: '<notice-disabled@example.org>',
            cleanBody: 'Reply text.',
            attachments: [$this->realAttachment()],
        );

        $this->assertSame([], $this->fakeNotifier->sent);
        $this->assertStringContainsString(__('notifications.inbound.attachments_ignored.comment_note'), $comment->body);
        $this->assertNull(IncomingEmail::query()
            ->where('message_id', '<notice-disabled@example.org>')
            ->value('attachment_notice_sent_at'));
    }

    public function test_duplicate_processing_does_not_send_duplicate_attachment_rejection_notice(): void
    {
        $sender = $this->createUser();
        $ticket = $this->createTicket($sender);
        $messageId = '<duplicate-message@example.org>';

        $firstComment = $this->service()->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: 'requester@example.org',
            messageId: $messageId,
            cleanBody: 'Reply text.',
            attachments: [$this->realAttachment()],
        );
        $secondComment = $this->service()->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: 'requester@example.org',
            messageId: $messageId,
            cleanBody: 'Reply text.',
            attachments: [$this->realAttachment()],
        );

        $this->assertSame($firstComment->id, $secondComment->id);
        $this->assertSame(1, TicketComment::query()->count());
        $this->assertCount(1, $this->fakeNotifier->sent);
    }

    public function test_inline_signature_image_does_not_trigger_rejection_notice(): void
    {
        $sender = $this->createUser();
        $ticket = $this->createTicket($sender);

        $comment = $this->service()->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: 'requester@example.org',
            messageId: '<inline-signature@example.org>',
            cleanBody: 'Reply text.',
            attachments: [[
                'filename' => 'logo.png',
                'mime_type' => 'image/png',
                'disposition' => 'inline',
                'content_id' => 'signature-logo',
                'size' => 4096,
            ]],
        );

        $this->assertSame([], $this->fakeNotifier->sent);
        $this->assertStringNotContainsString(__('notifications.inbound.attachments_ignored.comment_note'), $comment->body);
    }

    public function test_attachment_rejection_notice_uses_loop_prevention_headers_and_no_tokenized_reply_to(): void
    {
        $sender = $this->createUser(['preferred_locale' => 'en']);
        $ticket = $this->createTicket($sender);

        Mail::shouldReceive('send')
            ->once()
            ->withArgs(function (string $view, array $data, callable $callback): bool {
                $email = new Email();
                $callback(new Message($email));

                $headers = $email->getHeaders();
                $to = array_map(fn ($address) => $address->getAddress(), $email->getTo());

                $this->assertSame('emails.inbound-attachment-rejected', $view);
                $this->assertSame(['sender@example.org'], $to);
                $this->assertSame('auto-generated', $headers->get('Auto-Submitted')?->getBodyAsString());
                $this->assertSame('All', $headers->get('X-Auto-Response-Suppress')?->getBodyAsString());
                $this->assertNull($headers->get('Reply-To'));

                return true;
            });

        (new InboundAttachmentRejectedNotifier())->send($ticket, 'sender@example.org', 'en');
    }

    private function service(): InboundEmailReplyService
    {
        return app(InboundEmailReplyService::class);
    }

    private function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($this->userRole->id);

        return $user;
    }

    private function createTicket(User $requester): Ticket
    {
        return Ticket::query()->create([
            'ticket_number' => 'T-TEST-'.Str::upper(Str::random(8)),
            'subject' => 'Inbound test ticket',
            'description' => 'Inbound test description',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'ticket_status_id' => $this->defaultStatus->id,
            'ticket_priority_id' => $this->defaultPriority->id,
            'ticket_category_id' => $this->defaultCategory->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function realAttachment(): array
    {
        return [
            'filename' => 'screenshot.png',
            'mime_type' => 'image/png',
            'disposition' => 'attachment',
            'size' => 120_000,
        ];
    }
}

class FakeInboundAttachmentRejectedNotifier extends InboundAttachmentRejectedNotifier
{
    /**
     * @var array<int, array{ticket_id: int, email: string, locale: string}>
     */
    public array $sent = [];

    public function send(Ticket $ticket, string $senderEmail, string $locale): void
    {
        $this->sent[] = [
            'ticket_id' => $ticket->id,
            'email' => $senderEmail,
            'locale' => $locale,
        ];
    }
}
