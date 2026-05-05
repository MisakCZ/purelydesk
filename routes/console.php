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

Artisan::command('helpdesk:fetch-inbound-mail', function (): int {
    try {
        $counts = app(\App\Services\InboundMaildirProcessor::class)->fetch();
    } catch (\RuntimeException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    $this->info(__('notifications.inbound.console.finished', $counts));

    return 0;
})->purpose('Fetch and process inbound helpdesk replies from Maildir');

Schedule::command('helpdesk:close-resolved-tickets')->hourly();
Schedule::command('helpdesk:fetch-inbound-mail')->everyFiveMinutes();
