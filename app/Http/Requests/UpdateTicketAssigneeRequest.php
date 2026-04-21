<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketAssigneeRequest extends FormRequest
{
    protected $errorBag = 'ticketAssignee';

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
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
