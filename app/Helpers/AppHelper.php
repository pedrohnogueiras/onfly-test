<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

class AppHelper
{
    public static function createExternalReference($prefix = ''): string
    {
        return ($prefix ? $prefix . '-' : '') . Str::orderedUuid()->toString();
    }
}
