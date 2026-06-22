<?php

declare(strict_types=1);

namespace App\DTO\User;

use Spatie\LaravelData\Data;

class RegisterRequestDTO extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }
}
