<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    protected $errorBag = 'ticketStatus';

    public function authorize(): bool
    {
        // TODO: Replace with policy or role-based internal admin check when auth is integrated.
        return true;
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
