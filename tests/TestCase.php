<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Gera os headers HTTP necessários para uma requisição autenticada.
     *
     * Produz um JWT válido via JwtService (payload sub = $user->ref)
     * e inclui o header X-Request-Id obrigatório pelo middleware ValidatedRequestId.
     *
     * @param  User  $user  Usuário cujo JWT será gerado.
     * @return array<string, string>
     */
    protected function authHeaders(User $user): array
    {
        /** @var JwtService $jwt */
        $jwt = app(JwtService::class);
        $token = $jwt->encode($user);

        return [
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => (string) Str::uuid(),
        ];
    }

    /**
     * Gera apenas o header X-Request-Id para rotas sem autenticação
     * que ainda exijam o middleware ValidatedRequestId.
     *
     * @return array<string, string>
     */
    protected function requestIdHeader(): array
    {
        return [
            'X-Request-Id' => (string) Str::uuid(),
        ];
    }
}
