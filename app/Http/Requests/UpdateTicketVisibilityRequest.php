<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketVisibilityRequest extends FormRequest
{
    protected $errorBag = 'ticketVisibility';

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && app(TicketPolicy::class)->updateVisibility(app(HelpdeskAuth::class)->user(), $ticket);
    }

    public function rules(): array
    {
        return [
            'visibility' => ['required', 'in:public,internal,private'],
        ];
    }
}
