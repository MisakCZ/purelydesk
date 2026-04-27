<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\HelpdeskAuth;
use Illuminate\Foundation\Http\FormRequest;
use Closure;

class UpdateTicketAssigneeRequest extends FormRequest
{
    protected $errorBag = 'ticketAssignee';

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && app(TicketPolicy::class)->updateAssignee(app(HelpdeskAuth::class)->user(), $ticket);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $isAssignableSolver = User::query()
                        ->assignableSolvers()
                        ->whereKey((int) $value)
                        ->exists();

                    if (! $isAssignableSolver) {
                        $fail(__('tickets.validation.assignee_must_be_solver'));
                    }
                },
            ],
        ];
    }
}
