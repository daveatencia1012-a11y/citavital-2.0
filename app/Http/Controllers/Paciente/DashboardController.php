<?php

namespace App\Http\Controllers\Paciente;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        $upcoming = $patient->appointments()
            ->with(['doctor:id,first_name,last_name', 'specialty:id,name'])
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(4)
            ->get();

        $recentHistory = $patient->appointments()
            ->with(['doctor:id,first_name,last_name', 'specialty:id,name'])
            ->history()
            ->orderByDesc('scheduled_at')
            ->limit(4)
            ->get();

        return view('paciente.dashboard', [
            'paciente' => $patient,
            'proximasCitas' => $upcoming,
            'historialReciente' => $recentHistory,
            'proximasCount' => $patient->appointments()->upcoming()->count(),
            'pendientesCount' => $patient->appointments()
                ->where('status', AppointmentStatus::SCHEDULED->value)
                ->count(),
            'realizadasCount' => $patient->appointments()
                ->where('status', AppointmentStatus::COMPLETED->value)
                ->count(),
            'historialCount' => $patient->appointments()->history()->count(),
        ]);
    }
}
