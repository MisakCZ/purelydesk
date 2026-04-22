<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Ticket;
use App\Policies\AnnouncementPolicy;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);

        View::composer('layouts.admin', function ($view): void {
            $currentUser = app(HelpdeskAuth::class)->user();

            $view->with('canManageAnnouncements', app(AnnouncementPolicy::class)->manage($currentUser));
        });
    }
}
