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

Artisan::command('helpdesk:notify-expected-resolution-deadlines', function (): int {
    $counts = app(\App\Services\ExpectedResolutionDeadlineNotificationService::class)->notifyDueDeadlines();

    $this->info(__('notifications.expected_resolution_deadlines.console.finished', $counts));

    return 0;
})->purpose('Send expected resolution due soon and overdue reminders');

Schedule::command('helpdesk:close-resolved-tickets')->hourly();
Schedule::command('helpdesk:fetch-inbound-mail')->everyFiveMinutes();
Schedule::command('helpdesk:notify-expected-resolution-deadlines')->hourly();
