<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()
            ->with([
                'status:id,name,color',
                'priority:id,name,color',
                'requester:id,name',
                'assignee:id,name',
            ])
            ->orderByDesc('updated_at')
            ->get();

        return view('tickets.index', [
            'tickets' => $tickets,
        ]);
    }
}
