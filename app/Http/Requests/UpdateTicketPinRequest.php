<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketPinRequest extends FormRequest
{
    protected $errorBag = 'ticketPin';

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && app(TicketPolicy::class)->updatePin(app(HelpdeskAuth::class)->user(), $ticket);
    }

    public function rules(): array
    {
        return [
            'pinned' => ['required', 'boolean'],
        ];
    }
}
