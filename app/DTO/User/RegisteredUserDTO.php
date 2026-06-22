<?php

declare(strict_types=1);

namespace App\DTO\User;

use App\Models\User;
use Spatie\LaravelData\Data;

class RegisteredUserDTO extends Data
{
    public function __construct(
        public string $ref,
        public string $name,
        public string $email,
        public bool $is_admin,
        public string $api_key,
        public string $criado_em,
    ) {
    }

    public static function fromUser(User $user, string $apiKey): self
    {
        return new self(
            ref: $user->ref,
            name: $user->name,
            email: $user->email,
            is_admin: (bool) $user->is_admin,
            api_key: $apiKey,
            criado_em: $user->created_at->format('Y-m-d H:i:s'),
        );
    }
}
