<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.MissingImport)
 */
class UserStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'Fulano de Tal',
            'email' => 'fulano@onfly.com.br',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ], $overrides);
    }

    public function test_registra_usuario_e_retorna_201_com_estrutura_esperada(): void
    {
        $response = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'ref',
                    'name',
                    'email',
                    'is_admin',
                    'api_key',
                    'criado_em',
                ],
            ])
            ->assertJsonPath('data.name', 'Fulano de Tal')
            ->assertJsonPath('data.email', 'fulano@onfly.com.br')
            ->assertJsonPath('data.is_admin', false);

        $this->assertNotEmpty($response->json('data.api_key'));
        $this->assertStringStartsWith('usr-', $response->json('data.ref'));
    }

    public function test_persiste_usuario_com_senha_hasheada(): void
    {
        $response = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'fulano@onfly.com.br',
            'is_admin' => false,
        ]);

        $user = User::where('email', 'fulano@onfly.com.br')->firstOrFail();

        $this->assertNotSame('senha-segura-123', $user->password);
        $this->assertTrue(Hash::check('senha-segura-123', $user->password));
    }

    public function test_api_key_retornada_funciona_no_endpoint_de_token(): void
    {
        $register = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());
        $register->assertStatus(201);

        $apiKey = $register->json('data.api_key');

        $tokenResponse = $this->postJson('/auth/token', ['x_api_key' => $apiKey], $this->requestIdHeader());

        $tokenResponse->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->assertJsonFragment(['token_type' => 'Bearer']);
    }

    public function test_is_admin_do_payload_e_honrado(): void
    {
        $payload = $this->validPayload(['is_admin' => true]);

        $response = $this->postJson(route('users.store'), $payload, $this->requestIdHeader());

        $response->assertStatus(201)
            ->assertJsonPath('data.is_admin', true);

        $this->assertDatabaseHas('users', [
            'email' => 'fulano@onfly.com.br',
            'is_admin' => true,
        ]);
    }

    public function test_is_admin_default_false_quando_omitido(): void
    {
        $response = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());

        $response->assertStatus(201)
            ->assertJsonPath('data.is_admin', false);

        $this->assertDatabaseHas('users', [
            'email' => 'fulano@onfly.com.br',
            'is_admin' => false,
        ]);
    }

    public function test_retorna_422_para_email_duplicado(): void
    {
        User::factory()->create(['email' => 'fulano@onfly.com.br']);

        $response = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_retorna_422_para_email_invalido(): void
    {
        $response = $this->postJson(
            route('users.store'),
            $this->validPayload(['email' => 'nao-e-email']),
            $this->requestIdHeader(),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_retorna_422_sem_nome(): void
    {
        $payload = $this->validPayload();
        unset($payload['nome']);

        $response = $this->postJson(route('users.store'), $payload, $this->requestIdHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    public function test_retorna_422_para_senha_curta(): void
    {
        $response = $this->postJson(
            route('users.store'),
            $this->validPayload(['password' => 'abc', 'password_confirmation' => 'abc']),
            $this->requestIdHeader(),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_retorna_422_sem_confirmacao_de_senha(): void
    {
        $payload = $this->validPayload();
        unset($payload['password_confirmation']);

        $response = $this->postJson(route('users.store'), $payload, $this->requestIdHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_retorna_400_sem_x_request_id(): void
    {
        $response = $this->postJson(route('users.store'), $this->validPayload());

        $response->assertStatus(400);
    }

    /**
     * Testa o caminho feliz de email duplicado via validação (FormRequest, caminho normal).
     * A regra unique:users,email dispara 422 antes de atingir o controller.
     */
    public function test_email_duplicado_via_validacao_retorna_422(): void
    {
        User::factory()->create(['email' => 'fulano@onfly.com.br']);

        $response = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa o catch de QueryException com violação UNIQUE (SQLSTATE 23000) no controller.
     *
     * Cenário: a validação passa (email ainda não existe no momento da checagem),
     * mas a INSERT falha por race condition — simulado via mock do UserService.
     * O controller deve retornar 409 com mensagem em pt-BR, sem vazar stack trace.
     */
    public function test_unique_violation_sob_concorrencia_retorna_409(): void
    {
        $pdoException = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry');
        $pdoException->errorInfo = ['23000', 1062, 'Duplicate entry'];

        $queryException = new \Illuminate\Database\QueryException(
            'sqlite',
            'INSERT INTO `users`',
            [],
            $pdoException,
        );

        $this->mock(\App\Services\UserService::class)
            ->shouldReceive('register')
            ->once()
            ->andThrow($queryException);

        $response = $this->postJson(route('users.store'), $this->validPayload(), $this->requestIdHeader());

        $response->assertStatus(409)
            ->assertJsonPath('error', 'O e-mail informado já está cadastrado.');
    }
}
