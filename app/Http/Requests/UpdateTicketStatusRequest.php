<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    protected $errorBag = 'ticketStatus';

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && app(TicketPolicy::class)->updateStatus(app(HelpdeskAuth::class)->user(), $ticket);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', 'exists:ticket_statuses,id'],
        ];
    }
}
