<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('helpdesk:close-resolved-tickets', function (): int {
    $closedCount = app(\App\Services\TicketResolvedAutoCloseService::class)->closeDueTickets();

    $this->info(__('tickets.console.close_resolved.finished', ['count' => $closedCount]));

    return 0;
})->purpose('Close resolved helpdesk tickets after their auto-close deadline');

Schedule::command('helpdesk:close-resolved-tickets')->hourly();
