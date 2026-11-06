<?php

namespace App\Support;

use App\Models\User;

final class AdministratorAccess
{
    public static function allows(User $user): bool
    {
        $administratorEmail = (string) config('citavital.admin_email');

        return $administratorEmail !== ''
            && strcasecmp(trim((string) $user->email), trim($administratorEmail)) === 0;
    }
}
