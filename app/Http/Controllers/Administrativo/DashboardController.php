<?php

namespace App\Http\Controllers\Administrativo;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): View
    {
        return view('administrativo.dashboard', [
            'administrador' => $request->user(),
            ...$dashboardService->summary(),
        ]);
    }
}
