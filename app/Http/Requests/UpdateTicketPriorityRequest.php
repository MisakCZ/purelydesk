<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketPriorityRequest extends FormRequest
{
    protected $errorBag = 'ticketPriority';

    public function authorize(): bool
    {
        // TODO: Replace with policy or role-based internal admin check when auth is integrated.
        return true;
    }

    public function rules(): array
    {
        return [
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
        ];
    }
}
