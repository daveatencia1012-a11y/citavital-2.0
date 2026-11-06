<?php

namespace App\Http\Controllers\Administrativo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrativo\LoginRequest;
use App\Models\User;
use App\Support\AdministratorAccess;
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

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && AdministratorAccess::allows($user)) {
            return redirect()->route('administrativo.dashboard');
        }

        return view('administrativo.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos fallidos. Intenta de nuevo en {$seconds} segundos.",
            ]);
        }

        $credentials = $request->only('email', 'password');

        $authenticated = Auth::guard('web')->attemptWhen(
            $credentials,
            fn (User $user): bool => AdministratorAccess::allows($user),
        );

        if (! $authenticated) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales ingresadas no son correctas.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('administrativo.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Conserva el otro guard; destruye el ID anterior y renueva CSRF.
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()
            ->route('administrativo.login')
            ->with('status', 'Sesión cerrada correctamente.');
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        );
    }
}
