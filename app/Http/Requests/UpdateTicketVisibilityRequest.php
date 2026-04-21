<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketVisibilityRequest extends FormRequest
{
    protected $errorBag = 'ticketVisibility';

    public function authorize(): bool
    {
        // TODO: Replace with policy or role-based internal admin check when auth is integrated.
        return true;
    }

    public function rules(): array
    {
        return [
            'visibility' => ['required', 'in:public,restricted'],
        ];
    }
}
