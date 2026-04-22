<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketCategoryRequest extends FormRequest
{
    protected $errorBag = 'ticketCategory';

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && app(TicketPolicy::class)->updateCategory(app(HelpdeskAuth::class)->user(), $ticket);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
        ];
    }
}
