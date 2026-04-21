<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketCategoryRequest extends FormRequest
{
    protected $errorBag = 'ticketCategory';

    public function authorize(): bool
    {
        // TODO: Replace with policy or role-based internal admin check when auth is integrated.
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
        ];
    }
}
