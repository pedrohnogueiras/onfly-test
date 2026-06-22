<?php

declare(strict_types=1);

namespace App\DTO\User;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class RegisterRequestDTO extends Data
{
    public function __construct(
        #[MapInputName('nome')]
        public string $name,
        public string $email,
        public string $password,
        #[MapInputName('is_admin')]
        public bool $isAdmin = false,
    ) {
    }
}
