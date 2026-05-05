<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReplyToken;
use App\Models\User;
use Illuminate\Support\Str;

class TicketReplyTokenService
{
    public function tokenFor(Ticket $ticket, User $user): TicketReplyToken
    {
        return TicketReplyToken::query()->firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
            ],
            [
                'token' => $this->generateToken(),
            ],
        );
    }

    public function replyAddressFor(Ticket $ticket, User $user): string
    {
        $baseAddress = (string) config('helpdesk.inbound.reply_address');

        if (! config('helpdesk.inbound.use_plus_addressing')) {
            return $baseAddress;
        }

        $token = $this->tokenFor($ticket, $user)->token;

        [$localPart, $domain] = explode('@', $baseAddress, 2) + [null, null];

        if ($localPart === null || $domain === null) {
            return $baseAddress;
        }

        return $localPart.'+'.$token.'@'.$domain;
    }

    public function findToken(string $token): ?TicketReplyToken
    {
        if ($token === '') {
            return null;
        }

        return TicketReplyToken::query()
            ->with(['ticket', 'user'])
            ->where('token', $token)
            ->first();
    }

    private function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(48));
        } while (TicketReplyToken::query()->where('token', $token)->exists());

        return $token;
    }
}
