<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AdministratorAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! AdministratorAccess::allows($user)) {
            abort(403, 'No tienes permisos para acceder al área administrativa.');
        }

        return $next($request);
    }
}
