<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\TokenDataDTO;
use App\DTO\Auth\TokenRequestDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthService
{
    public const TOKEN_TYPE = 'Bearer';

    public function __construct(
        private readonly JwtService $jwtService,
    ) {
    }

    public function authenticate(TokenRequestDTO $tokenData): TokenDataDTO
    {
        $user = User::findByApiKey($tokenData->api_key);

        if (!$user) {
            throw new ModelNotFoundException('Credenciais Inválidas', 401);
        }

        return TokenDataDTO::from([
            'access_token' => $this->jwtService->encode($user),
            'token_type' => self::TOKEN_TYPE,
            'expires_in' => config('jwt.ttl'),
        ]);
    }
}
