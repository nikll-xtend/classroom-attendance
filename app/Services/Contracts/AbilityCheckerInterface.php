<?php

namespace App\Services\Contracts;

interface AbilityCheckerInterface
{
    public function can(string $ability): bool;

}
