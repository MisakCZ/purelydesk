<?php

namespace Tests\Feature;

use App\Models\IncomingEmail;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketReplyToken;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\InboundAttachmentRejectedNotifier;
use App\Services\TicketReplyTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class InboundMaildirPollingTest extends TestCase
{
    use RefreshDatabase;

    private string $mailBase;

    private string $maildir;

    private string $processedDir;

    private string $failedDir;

    private Role $userRole;

    private Role $solverRole;

    private Role $adminRole;

    private TicketStatus $newStatus;

    private TicketPriority $priority;

    private TicketCategory $category;

    private MaildirFakeInboundAttachmentRejectedNotifier $fakeNotifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailBase = sys_get_temp_dir().'/helpdesk-maildir-'.Str::random(16);
        $this->maildir = $this->mailBase.'/Maildir';
        $this->processedDir = $this->mailBase.'/Processed';
        $this->failedDir = $this->mailBase.'/Failed';

        File::makeDirectory($this->maildir.'/new', 0775, true);
        File::makeDirectory($this->maildir.'/cur', 0775, true);

        config()->set('helpdesk.inbound.mail_enabled', true);
        config()->set('helpdesk.inbound.mail_driver', 'maildir');
        config()->set('helpdesk.inbound.reply_address', 'helpdesk-replies@example.org');
        config()->set('helpdesk.inbound.use_plus_addressing', true);
        config()->set('helpdesk.inbound.maildir_path', $this->maildir);
        config()->set('helpdesk.inbound.maildir_processed_path', $this->processedDir);
        config()->set('helpdesk.inbound.maildir_failed_path', $this->failedDir);
        config()->set('helpdesk.inbound.maildir_max_messages', 50);
        config()->set('helpdesk.inbound.import_attachments', false);
        config()->set('helpdesk.inbound.notify_rejected_attachments', true);

        $this->fakeNotifier = new MaildirFakeInboundAttachmentRejectedNotifier();
        $this->app->instance(InboundAttachmentRejectedNotifier::class, $this->fakeNotifier);

        $this->userRole = $this->createRole('User', Role::SLUG_USER);
        $this->solverRole = $this->createRole('Solver', Role::SLUG_SOLVER);
        $this->adminRole = $this->createRole('Admin', Role::SLUG_ADMIN);

        $this->newStatus = TicketStatus::query()->create([
            'name' => 'New',
            'slug' => 'new',
            'sort_order' => 1,
            'is_default' => true,
        ]);

        $this->priority = TicketPriority::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'sort_order' => 1,
            'is_default' => true,
        ]);

        $this->category = TicketCategory::query()->create([
            'name' => 'Obecné',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->mailBase) && File::isDirectory($this->mailBase)) {
            File::deleteDirectory($this->mailBase);
        }

        parent::tearDown();
    }

    public function test_disabled_inbound_command_processes_nothing(): void
    {
        config()->set('helpdesk.inbound.mail_enabled', false);
        $file = $this->putMail('disabled.eml', $this->rawEmail(
            from: 'requester@example.org',
            to: 'helpdesk-replies@example.org',
            messageId: '<disabled@example.org>',
            body: 'Ignored while disabled.',
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $this->assertFileExists($file);
        $this->assertSame(0, TicketComment::query()->count());
    }

    public function test_valid_reply_token_creates_public_comment_and_moves_file_to_processed(): void
    {
        $sender = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($sender);
        $token = $this->replyToken($ticket, $sender);

        $this->putMail('valid-token.eml', $this->rawEmail(
            from: 'Requester <requester@example.org>',
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: '<valid-token@example.org>',
            subject: 'Re: [Helpdesk #'.$ticket->ticket_number.'] New comment',
            body: "Please add this information.\n\n".__('notifications.ticket.reply_marker', [], 'en')."\nOld text",
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $comment = TicketComment::query()->first();
        $this->assertNotNull($comment);
        $this->assertSame('public', $comment->visibility);
        $this->assertSame("Please add this information.", $comment->body);
        $this->assertFileExists($this->processedDir.'/valid-token.eml');
    }

    public function test_unknown_sender_does_not_create_comment_and_moves_file_to_failed(): void
    {
        $requester = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($requester);
        $token = $this->replyToken($ticket, $requester);

        $this->putMail('unknown-sender.eml', $this->rawEmail(
            from: 'unknown@example.org',
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: '<unknown-sender@example.org>',
            body: 'Unknown sender reply.',
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $this->assertSame(0, TicketComment::query()->count());
        $this->assertFileExists($this->failedDir.'/unknown-sender.eml');
        $this->assertSame('unknown_sender', IncomingEmail::query()->where('message_id', '<unknown-sender@example.org>')->value('failure_reason'));
    }

    public function test_private_ticket_reply_from_unauthorized_sender_does_not_create_comment(): void
    {
        $requester = $this->createUser('requester@example.org', [$this->userRole]);
        $other = $this->createUser('other@example.org', [$this->userRole]);
        $ticket = $this->createTicket($requester, ['visibility' => Ticket::VISIBILITY_PRIVATE]);

        $this->putMail('private-denied.eml', $this->rawEmail(
            from: $other->email,
            to: 'helpdesk-replies@example.org',
            messageId: '<private-denied@example.org>',
            subject: 'Re: [Helpdesk #'.$ticket->ticket_number.'] Private',
            body: 'I should not be allowed.',
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $this->assertSame(0, TicketComment::query()->count());
        $this->assertFileExists($this->failedDir.'/private-denied.eml');
    }

    public function test_duplicate_message_id_does_not_create_duplicate_comment(): void
    {
        $sender = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($sender);
        $token = $this->replyToken($ticket, $sender);
        $raw = $this->rawEmail(
            from: $sender->email,
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: '<duplicate-message@example.org>',
            body: 'Only once.',
        );

        $this->putMail('duplicate-1.eml', $raw);
        $this->artisan('helpdesk:fetch-inbound-mail')->assertExitCode(0);
        $this->putMail('duplicate-2.eml', $raw);
        $this->artisan('helpdesk:fetch-inbound-mail')->assertExitCode(0);

        $this->assertSame(1, TicketComment::query()->count());
        $this->assertFileExists($this->processedDir.'/duplicate-2.eml');
    }

    public function test_message_without_message_id_is_deduplicated_by_raw_hash(): void
    {
        $sender = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($sender);
        $token = $this->replyToken($ticket, $sender);
        $raw = $this->rawEmail(
            from: $sender->email,
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: null,
            body: 'Hash only.',
        );

        $this->putMail('hash-1.eml', $raw);
        $this->artisan('helpdesk:fetch-inbound-mail')->assertExitCode(0);
        $this->putMail('hash-2.eml', $raw);
        $this->artisan('helpdesk:fetch-inbound-mail')->assertExitCode(0);

        $this->assertSame(1, TicketComment::query()->count());
        $this->assertFileExists($this->processedDir.'/hash-2.eml');
    }

    public function test_auto_reply_is_ignored(): void
    {
        $sender = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($sender);
        $token = $this->replyToken($ticket, $sender);

        $this->putMail('auto-reply.eml', $this->rawEmail(
            from: $sender->email,
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: '<auto-reply@example.org>',
            body: 'Out of office.',
            extraHeaders: ['Auto-Submitted' => 'auto-replied'],
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $this->assertSame(0, TicketComment::query()->count());
        $this->assertFileExists($this->failedDir.'/auto-reply.eml');
        $this->assertSame('auto_reply', IncomingEmail::query()->where('message_id', '<auto-reply@example.org>')->value('failure_reason'));
    }

    public function test_parser_uses_text_above_reply_marker(): void
    {
        $sender = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($sender);
        $token = $this->replyToken($ticket, $sender);

        $this->putMail('marker.eml', $this->rawEmail(
            from: $sender->email,
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: '<marker@example.org>',
            body: "Fresh reply.\n\n".__('notifications.ticket.reply_marker', [], 'cs')."\nQuoted history.",
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $this->assertSame('Fresh reply.', TicketComment::query()->value('body'));
    }

    public function test_real_attachment_creates_comment_with_note_and_sends_notice(): void
    {
        $sender = $this->createUser('requester@example.org', [$this->userRole]);
        $ticket = $this->createTicket($sender);
        $token = $this->replyToken($ticket, $sender);

        $this->putMail('attachment.eml', $this->rawMultipartEmail(
            from: $sender->email,
            to: 'helpdesk-replies+'.$token.'@example.org',
            messageId: '<attachment@example.org>',
            body: 'See attached screenshot.',
        ));

        $this->artisan('helpdesk:fetch-inbound-mail')
            ->assertExitCode(0);

        $commentBody = (string) TicketComment::query()->value('body');
        $this->assertStringContainsString('See attached screenshot.', $commentBody);
        $this->assertStringContainsString(__('notifications.inbound.attachments_ignored.comment_note'), $commentBody);
        $this->assertCount(1, $this->fakeNotifier->sent);
    }

    public function test_scheduler_contains_inbound_mail_command(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('helpdesk:fetch-inbound-mail', Artisan::output());
    }

    private function createRole(string $name, string $slug): Role
    {
        return Role::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_system' => true,
        ]);
    }

    /**
     * @param  array<int, Role>  $roles
     */
    private function createUser(string $email, array $roles): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'name' => Str::before($email, '@'),
        ]);
        $user->roles()->attach(collect($roles)->pluck('id')->all());

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTicket(User $requester, array $attributes = []): Ticket
    {
        return Ticket::query()->create(array_merge([
            'ticket_number' => '2026-'.str_pad((string) (Ticket::query()->count() + 1), 3, '0', STR_PAD_LEFT),
            'subject' => 'Inbound ticket',
            'description' => 'Inbound ticket description',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'assignee_id' => null,
            'ticket_status_id' => $this->newStatus->id,
            'ticket_priority_id' => $this->priority->id,
            'ticket_category_id' => $this->category->id,
        ], $attributes));
    }

    private function replyToken(Ticket $ticket, User $user): string
    {
        return app(TicketReplyTokenService::class)->tokenFor($ticket, $user)->token;
    }

    private function putMail(string $filename, string $raw): string
    {
        $path = $this->maildir.'/new/'.$filename;
        File::put($path, $raw);

        return $path;
    }

    /**
     * @param  array<string, string>  $extraHeaders
     */
    private function rawEmail(string $from, string $to, ?string $messageId = '<message@example.org>', string $subject = 'Re: [Helpdesk #2026-001] Ticket', string $body = 'Reply body.', array $extraHeaders = []): string
    {
        $headers = [
            'From' => $from,
            'To' => $to,
            'Subject' => $subject,
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];

        if ($messageId !== null) {
            $headers = ['Message-ID' => $messageId] + $headers;
        }

        $headers = array_merge($headers, $extraHeaders);

        return collect($headers)
            ->map(fn (string $value, string $name): string => $name.': '.$value)
            ->implode("\r\n")."\r\n\r\n".$body."\r\n";
    }

    private function rawMultipartEmail(string $from, string $to, string $messageId, string $body): string
    {
        $boundary = 'helpdesk-boundary';

        return implode("\r\n", [
            'Message-ID: '.$messageId,
            'From: '.$from,
            'To: '.$to,
            'Subject: Re: ticket with attachment',
            'Content-Type: multipart/mixed; boundary="'.$boundary.'"',
            '',
            '--'.$boundary,
            'Content-Type: text/plain; charset=UTF-8',
            '',
            $body,
            '--'.$boundary,
            'Content-Type: image/png; name="screenshot.png"',
            'Content-Disposition: attachment; filename="screenshot.png"',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('fake-image-content'),
            '--'.$boundary.'--',
            '',
        ]);
    }
}

class MaildirFakeInboundAttachmentRejectedNotifier extends InboundAttachmentRejectedNotifier
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
