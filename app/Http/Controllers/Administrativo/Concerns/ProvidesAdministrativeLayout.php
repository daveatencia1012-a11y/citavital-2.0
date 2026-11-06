<?php

namespace App\Http\Controllers\Administrativo\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait ProvidesAdministrativeLayout
{
    /**
     * @return array<string, mixed>
     */
    protected function layoutData(Request $request): array
    {
        $ahora = now(config('app.timezone'));

        return [
            'administrador' => $request->user(),
            'currentDate' => Str::ucfirst(
                $ahora->copy()
                    ->locale('es')
                    ->translatedFormat('l, j \d\e F \d\e Y')
            ),
        ];
    }
}
