<?php

namespace App\Services;

use App\Services\Contracts\AbilityCheckerInterface;
use Illuminate\Support\Facades\Auth;

class SanctumAbilityChecker implements AbilityCheckerInterface
{
    public function can(string $ability): bool
    {
        return Auth::user()?->tokenCan($ability);
    }
}
