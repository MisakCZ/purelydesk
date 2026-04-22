<?php

namespace App\Http\Requests;

use App\Models\Announcement;
use App\Policies\AnnouncementPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(AnnouncementPolicy::class)->manage(app(HelpdeskAuth::class)->user());
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(array_keys(Announcement::typeOptions()))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
