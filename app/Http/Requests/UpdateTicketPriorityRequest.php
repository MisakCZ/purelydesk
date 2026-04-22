<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketPriorityRequest extends FormRequest
{
    protected $errorBag = 'ticketPriority';

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && app(TicketPolicy::class)->updatePriority(app(HelpdeskAuth::class)->user(), $ticket);
    }

    public function rules(): array
    {
        return [
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
        ];
    }
}
