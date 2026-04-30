<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private TicketStatus $defaultStatus;

    private TicketPriority $defaultPriority;

    private TicketCategory $defaultCategory;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('helpdesk.attachments.disk', 'local');
        config()->set('helpdesk.attachments.max_size_mb', 1);

        Storage::fake('local');

        $this->userRole = Role::query()->create([
            'name' => 'User',
            'slug' => Role::SLUG_USER,
            'is_system' => true,
        ]);

        $this->solverRole = Role::query()->create([
            'name' => 'Solver',
            'slug' => Role::SLUG_SOLVER,
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

    public function test_user_can_create_ticket_with_attachment(): void
    {
        $user = $this->createUserWithRole($this->userRole);
        $file = UploadedFile::fake()->create('manual.pdf', 12, 'application/pdf');

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'subject' => 'Ticket with attachment',
                'description' => 'Attachment test description',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'attachments' => [$file],
            ])
            ->assertRedirect(route('tickets.index'));

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->assertSame($user->id, $attachment->uploader_id);
        $this->assertNull($attachment->ticket_comment_id);
        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($user)
            ->get(route('tickets.show', $attachment->ticket))
            ->assertOk()
            ->assertSeeText('manual.pdf');
    }

    public function test_user_can_add_public_comment_with_attachment(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket(['requester' => $requester]);
        $file = UploadedFile::fake()->create('comment-note.txt', 3, 'text/plain');

        $this->actingAs($requester)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Comment with attachment.',
                'attachments' => [$file],
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->assertNotNull($attachment->ticket_comment_id);
        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('comment-note.txt');
    }

    public function test_user_without_ticket_access_cannot_download_attachment(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $otherUser = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_INTERNAL,
        ]);
        $attachment = $this->createAttachment($ticket, $requester);

        $this->actingAs($otherUser)
            ->get(route('ticket-attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_private_ticket_attachment_is_not_available_to_unauthorized_watcher(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $watcher = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket([
            'requester' => $requester,
            'visibility' => Ticket::VISIBILITY_PRIVATE,
        ]);
        $ticket->watchers()->attach($watcher->id);
        $attachment = $this->createAttachment($ticket, $requester);

        $this->actingAs($watcher)
            ->get(route('ticket-attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_disallowed_attachment_type_is_rejected(): void
    {
        $user = $this->createUserWithRole($this->userRole);
        $file = UploadedFile::fake()->create('script.exe', 1, 'application/x-msdownload');

        $this->actingAs($user)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), [
                'subject' => 'Rejected executable',
                'description' => 'Attachment validation test',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'attachments' => [$file],
            ])
            ->assertRedirect(route('tickets.create'))
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_oversized_attachment_is_rejected(): void
    {
        $user = $this->createUserWithRole($this->userRole);
        $file = UploadedFile::fake()->create('large.pdf', 2048, 'application/pdf');

        $this->actingAs($user)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), [
                'subject' => 'Rejected large file',
                'description' => 'Attachment validation test',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'attachments' => [$file],
            ])
            ->assertRedirect(route('tickets.create'))
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_too_many_attachments_are_rejected(): void
    {
        config()->set('helpdesk.attachments.max_files', 2);

        $user = $this->createUserWithRole($this->userRole);

        $this->actingAs($user)
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), [
                'subject' => 'Too many attachments',
                'description' => 'Attachment count validation test',
                'priority_id' => $this->defaultPriority->id,
                'category_id' => $this->defaultCategory->id,
                'attachments' => [
                    UploadedFile::fake()->create('one.pdf', 1, 'application/pdf'),
                    UploadedFile::fake()->create('two.pdf', 1, 'application/pdf'),
                    UploadedFile::fake()->create('three.pdf', 1, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('tickets.create'))
            ->assertSessionHasErrors('attachments');

        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_image_attachment_is_rendered_as_preview(): void
    {
        $requester = $this->createUserWithRole($this->userRole);
        $ticket = $this->createTicket(['requester' => $requester]);
        $attachment = $this->createAttachment($ticket, $requester, [
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('ticket-attachments.preview', $attachment), false);
    }

    private function createUserWithRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function createTicket(array $overrides = []): Ticket
    {
        $requester = $overrides['requester'] ?? $this->createUserWithRole($this->userRole);
        $assignee = $overrides['assignee'] ?? null;

        return Ticket::query()->create([
            'ticket_number' => 'T-TEST-'.Str::upper(Str::random(8)),
            'subject' => $overrides['subject'] ?? 'Ticket '.Str::random(8),
            'description' => $overrides['description'] ?? 'Test description',
            'visibility' => $overrides['visibility'] ?? Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'assignee_id' => $assignee?->id,
            'ticket_status_id' => ($overrides['status'] ?? $this->defaultStatus)->id,
            'ticket_priority_id' => ($overrides['priority'] ?? $this->defaultPriority)->id,
            'ticket_category_id' => ($overrides['category'] ?? $this->defaultCategory)->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAttachment(Ticket $ticket, User $uploader, array $overrides = []): TicketAttachment
    {
        $path = $overrides['path'] ?? 'ticket-attachments/'.$ticket->id.'/test-file';
        Storage::disk('local')->put($path, 'test content');

        return TicketAttachment::query()->create([
            'ticket_id' => $ticket->id,
            'ticket_comment_id' => $overrides['ticket_comment_id'] ?? null,
            'user_id' => $uploader->id,
            'uploader_id' => $uploader->id,
            'visibility' => $overrides['visibility'] ?? 'public',
            'disk' => 'local',
            'path' => $path,
            'original_name' => $overrides['original_name'] ?? 'test.pdf',
            'mime_type' => $overrides['mime_type'] ?? 'application/pdf',
            'size' => $overrides['size'] ?? 12,
        ]);
    }
}
