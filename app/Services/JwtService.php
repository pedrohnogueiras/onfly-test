<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public function encode(User $user): string
    {
        $now = Carbon::now();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->ref,
            'iat' => $now->timestamp,
            'exp' => $now->copy()->addSeconds(config('jwt.ttl'))->timestamp,
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.alg'));
    }

    public function decode(string $jwt): object
    {
        return JWT::decode($jwt, new Key(config('jwt.secret'), config('jwt.alg')));
    }
}
