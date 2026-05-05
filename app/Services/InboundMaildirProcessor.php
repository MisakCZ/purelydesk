<?php

namespace App\Services;

use App\Models\IncomingEmail;
use App\Models\Ticket;
use App\Models\TicketReplyToken;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InboundMaildirProcessor
{
    public function __construct(
        private readonly InboundEmailParser $parser,
        private readonly InboundEmailReplyService $replyService,
        private readonly TicketReplyTokenService $replyTokenService,
        private readonly TicketPolicy $ticketPolicy,
    ) {}

    /**
     * @return array{processed: int, ignored: int, failed: int}
     */
    public function fetch(): array
    {
        $counts = ['processed' => 0, 'ignored' => 0, 'failed' => 0];

        if (! config('helpdesk.inbound.mail_enabled')) {
            return $counts;
        }

        if (config('helpdesk.inbound.mail_driver') !== 'maildir') {
            throw new \RuntimeException('Unsupported inbound mail driver: '.config('helpdesk.inbound.mail_driver'));
        }

        foreach ($this->mailFiles() as $filePath) {
            $result = $this->processFile($filePath);
            $counts[$result]++;
        }

        return $counts;
    }

    /**
     * @return array<int, string>
     */
    private function mailFiles(): array
    {
        $basePath = rtrim((string) config('helpdesk.inbound.maildir_path'), '/');
        $files = [];

        foreach (['new', 'cur'] as $folder) {
            $path = $basePath.'/'.$folder;

            if (! is_dir($path)) {
                continue;
            }

            foreach (glob($path.'/*') ?: [] as $file) {
                if (is_file($file)) {
                    $files[] = $file;
                }
            }
        }

        sort($files);

        return array_slice($files, 0, max(0, (int) config('helpdesk.inbound.maildir_max_messages', 50)));
    }

    private function processFile(string $filePath): string
    {
        $raw = @file_get_contents($filePath);

        if ($raw === false) {
            Log::error('Unable to read inbound mail file.', ['path' => $filePath]);
            $this->moveTo($filePath, (string) config('helpdesk.inbound.maildir_failed_path'));

            return 'failed';
        }

        try {
            $status = $this->processRaw($raw);
        } catch (\Throwable $exception) {
            Log::error('Inbound mail processing failed.', [
                'path' => $filePath,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            $this->recordFailure($raw, $exception->getMessage());
            $status = 'failed';
        }

        $targetPath = $status === 'processed'
            ? (string) config('helpdesk.inbound.maildir_processed_path')
            : (string) config('helpdesk.inbound.maildir_failed_path');

        $this->moveTo($filePath, $targetPath);

        return $status;
    }

    private function processRaw(string $raw): string
    {
        $rawHash = hash('sha256', $raw);
        $parsed = $this->parser->parse($raw);
        $messageKey = $parsed->messageId ?: 'hash:'.$rawHash;

        $duplicate = IncomingEmail::query()
            ->where('message_id', $messageKey)
            ->orWhere('raw_hash', $rawHash)
            ->first();

        if ($duplicate !== null) {
            return $duplicate->status === 'processed' ? 'processed' : 'ignored';
        }

        if ($this->isAutoReply($parsed)) {
            $this->recordIgnored($messageKey, $rawHash, $parsed, 'auto_reply');

            return 'ignored';
        }

        $sender = $this->findSender($parsed->fromEmail);

        if (! $sender instanceof User) {
            $this->recordIgnored($messageKey, $rawHash, $parsed, 'unknown_sender');

            return 'ignored';
        }

        $resolution = $this->resolveTicket($parsed, $sender);

        if ($resolution === null) {
            $this->recordIgnored($messageKey, $rawHash, $parsed, 'ticket_not_resolved', $sender);

            return 'ignored';
        }

        [$ticket, $replyToken] = $resolution;

        if (! $this->ticketPolicy->view($sender, $ticket) || ! $this->ticketPolicy->commentPublic($sender, $ticket)) {
            $this->recordIgnored($messageKey, $rawHash, $parsed, 'permission_denied', $sender, $ticket);

            return 'ignored';
        }

        $body = $this->cleanReplyBody($parsed->body);

        if ($body === '') {
            $this->recordIgnored($messageKey, $rawHash, $parsed, 'empty_body', $sender, $ticket);

            return 'ignored';
        }

        $comment = $this->replyService->processReply(
            ticket: $ticket,
            sender: $sender,
            senderEmail: (string) $parsed->fromEmail,
            messageId: $messageKey,
            cleanBody: $body,
            attachments: $parsed->attachments,
        );

        IncomingEmail::query()
            ->where('message_id', $messageKey)
            ->update([
                'raw_hash' => $rawHash,
                'status' => 'processed',
                'processed_at' => now(),
                'failure_reason' => null,
            ]);

        if ($replyToken instanceof TicketReplyToken) {
            $replyToken->forceFill(['last_used_at' => now()])->save();
        }

        return $comment->exists ? 'processed' : 'failed';
    }

    private function resolveTicket(InboundParsedEmail $email, User $sender): ?array
    {
        $tokenValue = $this->extractToken($email);
        $replyToken = $tokenValue !== null ? $this->replyTokenService->findToken($tokenValue) : null;

        if ($replyToken instanceof TicketReplyToken) {
            if ((int) $replyToken->user_id !== (int) $sender->id) {
                return null;
            }

            return [$replyToken->ticket, $replyToken];
        }

        $ticketNumber = $this->extractTicketNumber($email->subject);

        if ($ticketNumber === null) {
            return null;
        }

        $ticket = Ticket::query()->where('ticket_number', $ticketNumber)->first();

        if (! $ticket instanceof Ticket) {
            return null;
        }

        if ((int) $ticket->requester_id !== (int) $sender->id && (int) $ticket->assignee_id !== (int) $sender->id) {
            return null;
        }

        return [$ticket, null];
    }

    private function extractToken(InboundParsedEmail $email): ?string
    {
        $baseAddress = (string) config('helpdesk.inbound.reply_address');
        $localPart = Str::before($baseAddress, '@');
        $domain = Str::after($baseAddress, '@');

        foreach ($email->recipientAddresses as $address) {
            if (preg_match('/^'.preg_quote($localPart, '/').'\+([a-z0-9]+)@'.preg_quote($domain, '/').'$/i', $address, $matches)) {
                return Str::lower($matches[1]);
            }
        }

        foreach ([$email->subject, $email->body] as $value) {
            if (preg_match('/reply-token:\s*([a-z0-9]+)/i', $value, $matches)) {
                return Str::lower($matches[1]);
            }
        }

        return null;
    }

    private function extractTicketNumber(string $subject): ?string
    {
        if (preg_match('/\[Helpdesk\s+#([^\]]+)\]/i', $subject, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/\b(20\d{2}-\d{3,})\b/', $subject, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\b(T-[A-Z0-9-]+)\b/i', $subject, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function findSender(?string $email): ?User
    {
        if ($email === null || $email === '') {
            return null;
        }

        return User::query()
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->first();
    }

    private function isAutoReply(InboundParsedEmail $email): bool
    {
        $autoSubmitted = Str::lower((string) $email->firstHeader('Auto-Submitted'));
        $precedence = Str::lower((string) $email->firstHeader('Precedence'));

        return ($autoSubmitted !== '' && $autoSubmitted !== 'no')
            || in_array($precedence, ['bulk', 'junk'], true)
            || $email->firstHeader('X-Auto-Response-Suppress') !== null
            || $email->firstHeader('List-Id') !== null;
    }

    private function cleanReplyBody(string $body): string
    {
        $markers = [
            __('notifications.ticket.reply_marker', [], 'cs'),
            __('notifications.ticket.reply_marker', [], 'en'),
        ];

        foreach ($markers as $marker) {
            $position = mb_stripos($body, $marker);

            if ($position !== false) {
                $body = mb_substr($body, 0, $position);
                break;
            }
        }

        $lines = [];

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            if (preg_match('/^\s*>/', $line)) {
                continue;
            }

            if (preg_match('/^\s*On .+ wrote:\s*$/i', $line) || preg_match('/^-{2,}\s*Original Message\s*-{2,}$/i', $line)) {
                break;
            }

            $lines[] = rtrim($line);
        }

        return trim(implode("\n", $lines));
    }

    private function recordIgnored(string $messageKey, string $rawHash, InboundParsedEmail $email, string $reason, ?User $sender = null, ?Ticket $ticket = null): void
    {
        IncomingEmail::query()->firstOrCreate(
            ['message_id' => $messageKey],
            [
                'raw_hash' => $rawHash,
                'ticket_id' => $ticket?->id,
                'sender_user_id' => $sender?->id,
                'sender_email' => $email->fromEmail,
                'status' => 'ignored',
                'failure_reason' => $reason,
                'failed_at' => now(),
            ],
        );
    }

    private function recordFailure(string $raw, string $reason): void
    {
        $rawHash = hash('sha256', $raw);

        IncomingEmail::query()->firstOrCreate(
            ['message_id' => 'hash:'.$rawHash],
            [
                'raw_hash' => $rawHash,
                'status' => 'failed',
                'failure_reason' => $reason,
                'failed_at' => now(),
            ],
        );
    }

    private function moveTo(string $filePath, string $targetDirectory): void
    {
        if (! is_dir($targetDirectory) && ! @mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            Log::error('Unable to create inbound mail target directory.', ['path' => $targetDirectory]);

            return;
        }

        $targetPath = rtrim($targetDirectory, '/').'/'.basename($filePath);

        if (file_exists($targetPath)) {
            $targetPath .= '.'.Str::random(8);
        }

        if (! @rename($filePath, $targetPath)) {
            Log::error('Unable to move inbound mail file.', [
                'source' => $filePath,
                'target' => $targetPath,
            ]);
        }
    }
}
