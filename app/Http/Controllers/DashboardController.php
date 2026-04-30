<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    use ResolvesHelpdeskUser;

    public function __invoke(DashboardDataService $dashboardData): View
    {
        $user = $this->requireHelpdeskUser(__('auth.login.required'), 'user');

        return view('dashboard.index', [
            'dashboard' => $dashboardData->forUser($user),
            'user' => $user,
        ]);
    }
}
