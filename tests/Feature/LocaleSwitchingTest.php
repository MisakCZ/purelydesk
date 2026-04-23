<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LocaleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_locale_selection_is_persisted_to_database(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('tickets.index'))
            ->post(route('locale.update'), [
                'locale' => 'cs',
            ])
            ->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_locale' => 'cs',
        ]);
    }

    public function test_authenticated_user_preferred_locale_is_loaded_on_later_request(): void
    {
        $user = User::factory()->create([
            'preferred_locale' => 'cs',
        ]);

        $this->actingAs($user)
            ->withSession([
                LocaleManager::SESSION_KEY => 'en',
            ])
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Seznam ticketů')
            ->assertDontSeeText('Tickets');
    }

    public function test_guest_locale_selection_falls_back_to_session_and_cookie(): void
    {
        $this->from(route('tickets.index'))
            ->post(route('locale.update'), [
                'locale' => 'cs',
            ])
            ->assertRedirect(route('tickets.index'));

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Seznam ticketů')
            ->assertDontSeeText('Tickets');
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('tickets.index'))
            ->post(route('locale.update'), [
                'locale' => 'de',
            ])
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHasErrors('locale');
    }

    public function test_initial_guest_request_uses_supported_browser_language(): void
    {
        $this->withHeaders([
            'Accept-Language' => 'cs-CZ,cs;q=0.9,en;q=0.8',
        ])->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Seznam ticketů')
            ->assertDontSeeText('Tickets');
    }

    public function test_initial_guest_request_falls_back_to_english_for_unsupported_browser_language(): void
    {
        $this->withHeaders([
            'Accept-Language' => 'de-DE,de;q=0.9',
        ])->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Tickets')
            ->assertDontSeeText('Seznam ticketů');
    }
}
