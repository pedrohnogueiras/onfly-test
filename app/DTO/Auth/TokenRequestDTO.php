<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class TokenRequestDTO extends Data
{
    public function __construct(
        #[MapInputName('x_api_key')]
        public string $api_key,
    ) {
    }
}
