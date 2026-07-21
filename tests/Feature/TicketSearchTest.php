<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketSearchTest extends TestCase
{
    use RefreshDatabase;

    private Role $userRole;

    private Role $solverRole;

    private Role $adminRole;

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    private TicketPriority $priority;

    private TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRole = $this->createRole('User', Role::SLUG_USER);
        $this->solverRole = $this->createRole('Solver', Role::SLUG_SOLVER);
        $this->adminRole = $this->createRole('Admin', Role::SLUG_ADMIN);
        $this->openStatus = $this->createStatus('Open', 'open');
        $this->closedStatus = $this->createStatus('Closed', 'closed', true);
        $this->priority = TicketPriority::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'sort_order' => 1,
            'is_default' => true,
        ]);
        $this->category = TicketCategory::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }

    public function test_search_still_matches_subject_and_description_but_not_unrelated_tickets(): void
    {
        $user = $this->createUser($this->userRole);
        $subjectMatch = $this->createTicket($user, ['subject' => 'Unique printer outage']);
        $descriptionMatch = $this->createTicket($user, ['description' => 'The unique VPN phrase is here']);
        $unrelated = $this->createTicket($user, ['subject' => 'Unrelated request', 'description' => 'Nothing relevant']);

        $subjectResponse = $this->actingAs($user)->get(route('tickets.index', ['search' => 'printer outage']));
        $subjectResponse->assertOk();
        $this->assertTicketIds($subjectResponse->viewData('tickets')->pluck('id')->all(), [$subjectMatch->id]);

        $descriptionResponse = $this->get(route('tickets.index', ['search' => 'unique VPN phrase']));
        $descriptionResponse->assertOk();
        $this->assertTicketIds($descriptionResponse->viewData('tickets')->pluck('id')->all(), [$descriptionMatch->id]);
        $this->assertNotContains($unrelated->id, $descriptionResponse->viewData('tickets')->pluck('id')->all());
    }

    public function test_percent_and_underscore_are_not_treated_as_unrestricted_wildcards(): void
    {
        $user = $this->createUser($this->userRole);
        $this->createTicket($user, ['subject' => 'Ordinary searchable ticket']);

        foreach (['%', '_'] as $search) {
            $response = $this->actingAs($user)->get(route('tickets.index', ['search' => $search]));

            $response->assertOk();
            $this->assertSame(0, $response->viewData('tickets')->total());
        }
    }

    public function test_public_comments_and_nested_replies_are_searchable_without_duplicate_tickets(): void
    {
        $user = $this->createUser($this->userRole);
        $ticket = $this->createTicket($user);
        $root = $this->comment($ticket, $user, 'public', 'Shared searchable phrase');
        $this->comment($ticket, $user, 'public', 'Shared searchable phrase repeated');
        $this->comment($ticket, $user, 'public', 'Nested reply marker', $root);

        $commentResponse = $this->actingAs($user)->get(route('tickets.index', ['search' => 'Shared searchable phrase']));
        $commentResponse->assertOk();
        $this->assertSame(1, $commentResponse->viewData('tickets')->total());
        $this->assertSame([$ticket->id], $commentResponse->viewData('tickets')->pluck('id')->all());

        $replyResponse = $this->get(route('tickets.index', ['search' => 'Nested reply marker']));
        $replyResponse->assertOk();
        $this->assertSame([$ticket->id], $replyResponse->viewData('tickets')->pluck('id')->all());
    }

    public function test_solver_and_admin_can_search_visible_internal_note_content(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);
        $admin = $this->createUser($this->adminRole);
        $ticket = $this->createTicket($requester, ['visibility' => Ticket::VISIBILITY_INTERNAL]);
        $this->comment($ticket, $solver, 'internal', 'Unique diagnostic capacitor detail');

        foreach ([$solver, $admin] as $actor) {
            $response = $this->actingAs($actor)->get(route('tickets.index', ['search' => 'diagnostic capacitor']));

            $response->assertOk();
            $this->assertSame([$ticket->id], $response->viewData('tickets')->pluck('id')->all());
        }
    }

    public function test_regular_user_cannot_search_internal_note_content_even_as_requester(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);
        $ticket = $this->createTicket($requester, ['visibility' => Ticket::VISIBILITY_INTERNAL]);
        $this->comment($ticket, $solver, 'internal', 'Secret internal diagnostic phrase');

        $response = $this->actingAs($requester)->get(route('tickets.index', ['search' => 'Secret internal diagnostic']));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('tickets')->total());
    }

    public function test_solver_cannot_find_unauthorized_private_ticket_through_internal_note(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole);
        $author = $this->createUser($this->solverRole);
        $ticket = $this->createTicket($requester, ['visibility' => Ticket::VISIBILITY_PRIVATE]);
        $this->comment($ticket, $author, 'internal', 'Private internal search marker');

        $response = $this->actingAs($solver)->get(route('tickets.index', ['search' => 'Private internal search marker']));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('tickets')->total());
    }

    public function test_czech_internal_note_aliases_match_visible_tickets_with_any_internal_note(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole, ['preferred_locale' => 'cs']);
        $ticket = $this->createTicket($requester);
        $this->comment($ticket, $solver, 'internal', 'Text bez hledaného aliasu');

        foreach (['Interní', 'INTERNÍ', 'interni', '  interní  '] as $search) {
            $response = $this->actingAs($solver)->get(route('tickets.index', ['search' => $search]));

            $response->assertOk();
            $this->assertSame([$ticket->id], $response->viewData('tickets')->pluck('id')->all());
        }
    }

    public function test_english_internal_note_aliases_match_visible_tickets_with_any_internal_note(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole, ['preferred_locale' => 'en']);
        $ticket = $this->createTicket($requester);
        $this->comment($ticket, $solver, 'internal', 'No alias occurs in this note');

        foreach (['internal', 'Internal Note'] as $search) {
            $response = $this->actingAs($solver)->get(route('tickets.index', ['search' => $search]));

            $response->assertOk();
            $this->assertSame([$ticket->id], $response->viewData('tickets')->pluck('id')->all());
        }
    }

    public function test_longer_phrase_is_not_treated_as_internal_note_alias(): void
    {
        $requester = $this->createUser($this->userRole);
        $solver = $this->createUser($this->solverRole, ['preferred_locale' => 'cs']);
        $ticket = $this->createTicket($requester);
        $this->comment($ticket, $solver, 'internal', 'Poznámka bez hledaného textu');

        $response = $this->actingAs($solver)->get(route('tickets.index', ['search' => 'interní systém']));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('tickets')->total());
    }

    public function test_regular_user_alias_does_not_reveal_internal_note_but_still_searches_public_content(): void
    {
        $user = $this->createUser($this->userRole, ['preferred_locale' => 'cs']);
        $solver = $this->createUser($this->solverRole);
        $internalOnly = $this->createTicket($user, ['subject' => 'Hidden note ticket']);
        $publicMatch = $this->createTicket($user, ['subject' => 'Interní systém dostupný uživateli']);
        $this->comment($internalOnly, $solver, 'internal', 'Obsah bez aliasu');

        $response = $this->actingAs($user)->get(route('tickets.index', ['search' => 'Interní']));

        $response->assertOk();
        $ids = $response->viewData('tickets')->pluck('id')->all();
        $this->assertContains($publicMatch->id, $ids);
        $this->assertNotContains($internalOnly->id, $ids);
    }

    public function test_comment_search_combines_with_existing_status_filter(): void
    {
        $user = $this->createUser($this->userRole);
        $openTicket = $this->createTicket($user, ['status' => $this->openStatus]);
        $closedTicket = $this->createTicket($user, ['status' => $this->closedStatus]);
        $this->comment($openTicket, $user, 'public', 'Combined filter marker');
        $this->comment($closedTicket, $user, 'public', 'Combined filter marker');

        $response = $this->actingAs($user)->get(route('tickets.index', [
            'search' => 'Combined filter marker',
            'status' => (string) $this->openStatus->id,
        ]));

        $response->assertOk();
        $this->assertSame([$openTicket->id], $response->viewData('tickets')->pluck('id')->all());
    }

    public function test_comment_search_preserves_archive_rules(): void
    {
        $admin = $this->createUser($this->adminRole);
        $active = $this->createTicket($admin);
        $archived = $this->createTicket($admin, [
            'archived_at' => now(),
            'archived_by_user_id' => $admin->id,
        ]);
        $this->comment($active, $admin, 'public', 'Archive search marker');
        $this->comment($archived, $admin, 'public', 'Archive search marker');

        $activeResponse = $this->actingAs($admin)->get(route('tickets.index', ['search' => 'Archive search marker']));
        $activeResponse->assertOk();
        $this->assertSame([$active->id], $activeResponse->viewData('tickets')->pluck('id')->all());

        $archivedResponse = $this->get(route('tickets.index', [
            'search' => 'Archive search marker',
            'archive' => 'archived',
        ]));
        $archivedResponse->assertOk();
        $this->assertSame([$archived->id], $archivedResponse->viewData('tickets')->pluck('id')->all());
    }

    public function test_comment_search_is_applied_to_main_and_pinned_ticket_queries(): void
    {
        $user = $this->createUser($this->userRole);
        $ticket = $this->createTicket($user, [
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);
        $this->comment($ticket, $user, 'public', 'Pinned comment search marker');

        $response = $this->actingAs($user)->get(route('tickets.index', ['search' => 'Pinned comment search marker']));

        $response->assertOk();
        $this->assertSame([$ticket->id], $response->viewData('tickets')->pluck('id')->all());
        $this->assertSame([$ticket->id], $response->viewData('pinnedTickets')->pluck('id')->all());
    }

    private function createRole(string $name, string $slug): Role
    {
        return Role::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_system' => true,
        ]);
    }

    private function createStatus(string $name, string $slug, bool $closed = false): TicketStatus
    {
        return TicketStatus::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => TicketStatus::query()->count() + 1,
            'is_default' => ! $closed,
            'is_closed' => $closed,
        ]);
    }

    private function createUser(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'preferred_locale' => 'en',
            'is_active' => true,
        ], $attributes));
        $user->roles()->attach($role);

        return $user;
    }

    private function createTicket(User $requester, array $attributes = []): Ticket
    {
        return Ticket::query()->create(array_merge([
            'ticket_number' => 'SEARCH-'.Str::upper(Str::random(8)),
            'subject' => 'Ticket '.Str::random(10),
            'description' => 'Ordinary ticket description',
            'visibility' => Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $requester->id,
            'assignee_id' => null,
            'ticket_status_id' => $this->openStatus->id,
            'ticket_priority_id' => $this->priority->id,
            'ticket_category_id' => $this->category->id,
        ], $this->normalizeTicketAttributes($attributes)));
    }

    private function normalizeTicketAttributes(array $attributes): array
    {
        if (($attributes['status'] ?? null) instanceof TicketStatus) {
            $attributes['ticket_status_id'] = $attributes['status']->id;
            unset($attributes['status']);
        }

        return $attributes;
    }

    private function comment(
        Ticket $ticket,
        User $author,
        string $visibility,
        string $body,
        ?TicketComment $parent = null,
    ): TicketComment {
        return $ticket->comments()->create([
            'user_id' => $author->id,
            'parent_id' => $parent?->id,
            'visibility' => $visibility,
            'body' => $body,
        ]);
    }

    /**
     * @param  array<int, int>  $actual
     * @param  array<int, int>  $expected
     */
    private function assertTicketIds(array $actual, array $expected): void
    {
        sort($actual);
        sort($expected);

        $this->assertSame($expected, $actual);
    }
}
