<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Cria um User com api_key conhecida e retorna [$user, $plainKey].
     *
     * @return array{0: User, 1: string}
     */
    private function createUserWithApiKey(): array
    {
        $user = User::factory()->create();
        $plainKey = $user->generateApiKey();

        return [$user, $plainKey];
    }

    // -------------------------------------------------------------------------
    // Testes
    // -------------------------------------------------------------------------

    public function test_retorna_token_com_api_key_valida(): void
    {
        [, $plainKey] = $this->createUserWithApiKey();

        $response = $this->postJson('/auth/token', ['x_api_key' => $plainKey], $this->requestIdHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJsonFragment(['token_type' => 'Bearer']);
    }

    public function test_retorna_401_com_api_key_invalida(): void
    {
        // Garante pelo menos um user no banco para não confundir com 422
        User::factory()->create();

        $response = $this->postJson('/auth/token', ['x_api_key' => 'chave-invalida-qualquer'], $this->requestIdHeader());

        $response->assertStatus(401)
            ->assertJsonFragment(['error' => 'Credenciais Inválidas']);
    }

    public function test_retorna_422_sem_x_api_key(): void
    {
        $response = $this->postJson('/auth/token', [], $this->requestIdHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['x_api_key']);
    }

    public function test_retorna_400_sem_x_request_id(): void
    {
        $response = $this->postJson('/auth/token', ['x_api_key' => 'qualquer']);

        $response->assertStatus(400);
    }

    public function test_token_gerado_e_aceito_em_rota_protegida(): void
    {
        [$user, $plainKey] = $this->createUserWithApiKey();

        // Obtém token via endpoint de autenticação
        $authResponse = $this->postJson('/auth/token', ['x_api_key' => $plainKey], $this->requestIdHeader());
        $authResponse->assertStatus(200);

        $token = $authResponse->json('access_token');

        // Usa o token numa rota protegida por JWT
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => (string) \Illuminate\Support\Str::uuid(),
        ];

        $this->getJson('/pedido', $headers)->assertStatus(200);
    }
}
