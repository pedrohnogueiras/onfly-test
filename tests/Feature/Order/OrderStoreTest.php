<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\OrderStatusEnum;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class OrderStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrderStatusSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Payload válido para criação de pedido.
     *
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'solicitante' => 'João Silva',
            'data_partida' => '10-07-2027',
            'data_retorno' => '20-07-2027',
            'destino' => [
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'pais' => 'Brasil',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Testes de sucesso
    // -------------------------------------------------------------------------

    public function test_cria_pedido_com_sucesso_e_retorna_201(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/pedido', $this->validPayload(), $this->authHeaders($user));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'ref',
                    'solicitante',
                    'data_partida',
                    'data_retorno',
                    'status',
                    'destino' => ['cidade', 'estado', 'pais'],
                    'criado_em',
                ],
            ]);
    }

    public function test_persiste_order_e_destination_no_banco(): void
    {
        $user = User::factory()->create();

        $this->postJson('/pedido', $this->validPayload(), $this->authHeaders($user))
            ->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'applicant' => 'João Silva',
            'departure_date' => '2027-07-10',
            'return_date' => '2027-07-20',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('destinations', [
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'Brasil',
        ]);
    }

    public function test_status_default_e_registred_quando_omitido(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/pedido', $this->validPayload(), $this->authHeaders($user));

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'Solicitado');

        $this->assertDatabaseHas('orders', [
            'applicant' => 'João Silva',
            'status_id' => OrderStatusEnum::Registred->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Testes de validação (422)
    // -------------------------------------------------------------------------

    public function test_422_sem_solicitante(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['solicitante']);

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['solicitante']);
    }

    public function test_422_data_partida_formato_invalido(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['data_partida'] = '2027-07-10'; // formato errado (Y-m-d)

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_partida']);
    }

    public function test_422_data_retorno_anterior_a_partida(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['data_retorno'] = '01-07-2027'; // anterior a 10-07-2027

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_retorno']);
    }

    public function test_422_sem_destino_cidade(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['destino']['cidade']);

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['destino.cidade']);
    }

    public function test_422_sem_destino_estado(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['destino']['estado']);

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['destino.estado']);
    }

    public function test_422_sem_destino_pais(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['destino']['pais']);

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['destino.pais']);
    }

    public function test_422_status_invalido(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['status'] = 99; // valor inexistente no enum

        $this->postJson('/pedido', $payload, $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // -------------------------------------------------------------------------
    // Testes de autenticação / headers
    // -------------------------------------------------------------------------

    public function test_401_sem_jwt(): void
    {
        $this->postJson('/pedido', $this->validPayload(), $this->requestIdHeader())
            ->assertStatus(401);
    }

    public function test_400_sem_request_id(): void
    {
        $user = User::factory()->create();

        $headers = ['Authorization' => 'Bearer ' . app(\App\Services\JwtService::class)->encode($user)];

        $this->postJson('/pedido', $this->validPayload(), $headers)
            ->assertStatus(400);
    }
}
