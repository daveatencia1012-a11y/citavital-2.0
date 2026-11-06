<?php

namespace App\Http\Controllers\Administrativo;

use App\Http\Controllers\Administrativo\Concerns\ProvidesAdministrativeLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\EstadisticaRequest;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Services\StatisticsService;
use Illuminate\View\View;

class EstadisticaController extends Controller
{
    use ProvidesAdministrativeLayout;

    public function __invoke(EstadisticaRequest $request, StatisticsService $service): View
    {
        return view('administrativo.estadisticas.index', [
            ...$this->layoutData($request), ...$service->summary($request->validated()),
            'medicos' => Doctor::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'especialidades' => Specialty::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
