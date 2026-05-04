<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class InboundAttachmentRejectedNotifier
{
    public function send(Ticket $ticket, string $senderEmail, string $locale): void
    {
        $number = $ticket->ticket_number ?? __('tickets.common.no_number', [], $locale);

        Mail::send('emails.inbound-attachment-rejected', [
            'ticket' => $ticket,
            'locale' => $locale,
            'ticketUrl' => route('tickets.show', $ticket),
        ], function (Message $message) use ($senderEmail, $locale, $number): void {
            $message
                ->to($senderEmail)
                ->subject(__('notifications.inbound.attachments_ignored.subject', [
                    'number' => $number,
                ], $locale));

            $headers = $message->getSymfonyMessage()->getHeaders();
            $headers->addTextHeader('Auto-Submitted', 'auto-generated');
            $headers->addTextHeader('X-Auto-Response-Suppress', 'All');
        });
    }
}
