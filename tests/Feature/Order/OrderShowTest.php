<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class OrderShowTest extends TestCase
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
     * Cria um Order com Destination associado para o user informado.
     */
    private function createOrderWithDestination(User $user): Order
    {
        $order = Order::factory()
            ->for($user)
            ->has(\App\Models\Destination::factory(), 'destination')
            ->create();

        return $order;
    }

    // -------------------------------------------------------------------------
    // Testes de sucesso
    // -------------------------------------------------------------------------

    public function test_retorna_200_e_estrutura_correta_para_pedido_proprio(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderWithDestination($user);

        $response = $this->getJson("/pedido/{$order->ref}", $this->authHeaders($user));

        $response->assertStatus(200)
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
            ])
            ->assertJsonPath('data.ref', $order->ref);
    }

    // -------------------------------------------------------------------------
    // Testes de erro (404)
    // -------------------------------------------------------------------------

    public function test_404_referencia_inexistente(): void
    {
        $user = User::factory()->create();

        $this->getJson('/pedido/ref-inexistente-xyz', $this->authHeaders($user))
            ->assertStatus(404)
            ->assertJsonFragment(['error' => 'Pedido não encontrado']);
    }

    public function test_404_pedido_de_outro_usuario(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = $this->createOrderWithDestination($owner);

        // $other tenta acessar pedido de $owner → isolamento → 404
        $this->getJson("/pedido/{$order->ref}", $this->authHeaders($other))
            ->assertStatus(404)
            ->assertJsonFragment(['error' => 'Pedido não encontrado']);
    }

    // -------------------------------------------------------------------------
    // Testes de admin — acesso a pedidos de outros usuários
    // -------------------------------------------------------------------------

    public function test_admin_busca_pedido_de_outro_usuario_retorna_200(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $order = $this->createOrderWithDestination($owner);

        $response = $this->getJson("/pedido/{$order->ref}", $this->authHeaders($admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.ref', $order->ref);
    }

    // -------------------------------------------------------------------------
    // Testes de autenticação / headers
    // -------------------------------------------------------------------------

    public function test_401_sem_jwt(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderWithDestination($user);

        $this->getJson("/pedido/{$order->ref}", $this->requestIdHeader())
            ->assertStatus(401);
    }

    public function test_400_sem_request_id(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderWithDestination($user);

        $this->getJson("/pedido/{$order->ref}")
            ->assertStatus(400);
    }
}
