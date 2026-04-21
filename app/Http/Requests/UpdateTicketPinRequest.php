<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketPinRequest extends FormRequest
{
    protected $errorBag = 'ticketPin';

    public function authorize(): bool
    {
        // TODO: Restrict pinning actions to internal administrators.
        return true;
    }

    public function rules(): array
    {
        return [
            'pinned' => ['required', 'boolean'],
        ];
    }
}
