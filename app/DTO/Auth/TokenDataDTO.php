<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Spatie\LaravelData\Data;

class TokenDataDTO extends Data
{
    public function __construct(
        public string $access_token,
        public string $token_type,
        public string $expires_in,
    ) {
    }
}
