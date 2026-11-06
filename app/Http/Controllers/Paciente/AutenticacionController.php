<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Paciente\LoginRequest;
use App\Services\PatientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AutenticacionController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(private readonly PatientAccessService $patientAccessService) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::guard('patient')->check()) {
            return redirect()->route('paciente.dashboard');
        }

        return view('paciente.login', [
            'demoHabilitado' => $this->patientAccessService->demoEnabled(),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'document_number' => "Demasiados intentos fallidos. Intenta de nuevo en {$seconds} segundos.",
            ]);
        }

        try {
            $patient = $this->patientAccessService->findDemoPatient(
                (string) $request->validated('document_number'),
            );
        } catch (ValidationException $exception) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw $exception;
        }

        RateLimiter::clear($throttleKey);

        Auth::guard('patient')->login($patient);
        $request->session()->regenerate(true);

        return redirect()->route('paciente.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();

        // Se regenera la sesión sin invalidarla por completo para no cerrar una
        // sesión administrativa que pudiera existir en el mismo navegador.
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()
            ->route('paciente.login')
            ->with('status', 'Sesión del paciente cerrada correctamente.');
    }

    private function throttleKey(Request $request): string
    {
        return 'paciente-login|'.Str::lower((string) $request->input('document_number')).'|'.$request->ip();
    }
}
