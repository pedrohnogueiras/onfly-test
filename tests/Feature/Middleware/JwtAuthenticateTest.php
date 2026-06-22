<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class JwtAuthenticateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrderStatusSeeder::class);
    }

    // Rota protegida por JWT usada nos testes
    private function protectedEndpoint(): string
    {
        return '/pedido';
    }

    public function test_401_sem_token(): void
    {
        $this->getJson($this->protectedEndpoint(), $this->requestIdHeader())
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Token não fornecido.']);
    }

    public function test_401_token_malformado(): void
    {
        $headers = array_merge($this->requestIdHeader(), [
            'Authorization' => 'Bearer este.nao.e.um.jwt.valido',
        ]);

        $this->getJson($this->protectedEndpoint(), $headers)
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Token inválido ou expirado.']);
    }

    public function test_401_sub_sem_usuario_correspondente(): void
    {
        // Gera um JWT com sub apontando para ref inexistente no banco
        $payload = [
            'iss' => config('app.url'),
            'sub' => 'usr-00000000-0000-0000-0000-000000000000',
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds(3600)->timestamp,
        ];

        $token = JWT::encode($payload, config('jwt.secret'), config('jwt.alg', 'HS256'));

        $headers = array_merge($this->requestIdHeader(), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->getJson($this->protectedEndpoint(), $headers)
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Usuário não encontrado.']);
    }

    public function test_passa_com_token_valido(): void
    {
        $user = User::factory()->create();

        $this->getJson($this->protectedEndpoint(), $this->authHeaders($user))
            ->assertStatus(200);
    }
}
