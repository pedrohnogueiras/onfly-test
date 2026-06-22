<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\OrderStatusEnum;
use App\Models\Destination;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class OrderListTest extends TestCase
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
     * Cria N pedidos (com destination) para o user informado.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    private function createOrdersForUser(User $user, int $count = 1): \Illuminate\Database\Eloquent\Collection
    {
        return Order::factory()
            ->count($count)
            ->for($user)
            ->has(Destination::factory(), 'destination')
            ->create();
    }

    /**
     * Cria um pedido com datas específicas (Y-m-d) para o user informado.
     */
    private function createOrderWithDates(User $user, string $departure, string $return): Order
    {
        return Order::factory()
            ->for($user)
            ->withDates($departure, $return)
            ->has(Destination::factory(), 'destination')
            ->create();
    }

    // -------------------------------------------------------------------------
    // Testes de sucesso — comportamento original
    // -------------------------------------------------------------------------

    public function test_lista_somente_pedidos_do_usuario_autenticado(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->createOrdersForUser($user1, 2);
        $this->createOrdersForUser($user2, 3);

        $response = $this->getJson('/pedido', $this->authHeaders($user1));

        $response->assertStatus(200);

        // user1 deve ver exatamente 2 pedidos, não 5
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_retorna_lista_vazia_quando_usuario_nao_tem_pedidos(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson('/pedido', $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_pedidos_ordenados_do_mais_recente_ao_mais_antigo(): void
    {
        $user = User::factory()->create();
        $orders = $this->createOrdersForUser($user, 3);

        $response = $this->getJson('/pedido', $this->authHeaders($user));
        $response->assertStatus(200);

        $refs = array_column($response->json('data'), 'ref');

        // A ordem deve ser decrescente por created_at (latest())
        // Os pedidos foram criados em sequência; o último criado tem maior created_at
        $orderedByDesc = $orders->sortByDesc('created_at')->pluck('ref')->values()->toArray();

        $this->assertSame($orderedByDesc, $refs);
    }

    // -------------------------------------------------------------------------
    // Testes de filtro por status
    // -------------------------------------------------------------------------

    public function test_filtra_pedidos_por_status_aprovado(): void
    {
        $user = User::factory()->create();

        // 2 Solicitados, 1 Aprovado, 1 Cancelado
        $this->createOrdersForUser($user, 2);

        Order::factory()
            ->for($user)
            ->approved()
            ->has(Destination::factory(), 'destination')
            ->create();

        Order::factory()
            ->for($user)
            ->cancelled()
            ->has(Destination::factory(), 'destination')
            ->create();

        $response = $this->getJson('/pedido?status=' . OrderStatusEnum::Approved->value, $this->authHeaders($user));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Aprovado', $data[0]['status']);
    }

    public function test_filtra_pedidos_por_status_cancelado(): void
    {
        $user = User::factory()->create();

        $this->createOrdersForUser($user, 1);

        Order::factory()
            ->for($user)
            ->cancelled()
            ->has(Destination::factory(), 'destination')
            ->count(2)
            ->create();

        $response = $this->getJson('/pedido?status=' . OrderStatusEnum::Cancelled->value, $this->authHeaders($user));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Testes de filtro por range de data de partida
    // -------------------------------------------------------------------------

    public function test_filtra_por_data_inicio_inclusive(): void
    {
        $user = User::factory()->create();

        // Dentro do range
        $this->createOrderWithDates($user, '2027-07-10', '2027-07-20');
        $this->createOrderWithDates($user, '2027-07-15', '2027-07-25');
        // Fora do range (antes)
        $this->createOrderWithDates($user, '2027-07-05', '2027-07-09');

        // Filtra: data_inicio = 10-07-2027 (inclusive)
        $response = $this->getJson('/pedido?data_inicio=10-07-2027', $this->authHeaders($user));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtra_por_data_fim_inclusive(): void
    {
        $user = User::factory()->create();

        // Dentro do range (departure_date <= 2027-07-15)
        $this->createOrderWithDates($user, '2027-07-10', '2027-07-20');
        $this->createOrderWithDates($user, '2027-07-15', '2027-07-25');
        // Fora do range (depois)
        $this->createOrderWithDates($user, '2027-07-20', '2027-07-30');

        // Filtra: data_fim = 15-07-2027 (inclusive)
        $response = $this->getJson('/pedido?data_fim=15-07-2027', $this->authHeaders($user));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtra_por_range_completo_inclusive_nas_bordas(): void
    {
        $user = User::factory()->create();

        // Exatamente na borda inicial
        $this->createOrderWithDates($user, '2027-07-10', '2027-07-20');
        // Dentro do range
        $this->createOrderWithDates($user, '2027-07-12', '2027-07-22');
        // Exatamente na borda final
        $this->createOrderWithDates($user, '2027-07-20', '2027-07-30');
        // Fora do range (antes)
        $this->createOrderWithDates($user, '2027-07-05', '2027-07-09');
        // Fora do range (depois)
        $this->createOrderWithDates($user, '2027-07-25', '2027-07-30');

        $response = $this->getJson(
            '/pedido?data_inicio=10-07-2027&data_fim=20-07-2027',
            $this->authHeaders($user),
        );

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Teste combinando status + range de data
    // -------------------------------------------------------------------------

    public function test_combina_filtro_status_e_range_de_data(): void
    {
        $user = User::factory()->create();

        // Aprovado dentro do range
        Order::factory()
            ->for($user)
            ->approved()
            ->withDates('2027-07-12', '2027-07-22')
            ->has(Destination::factory(), 'destination')
            ->create();

        // Aprovado fora do range
        Order::factory()
            ->for($user)
            ->approved()
            ->withDates('2027-08-01', '2027-08-10')
            ->has(Destination::factory(), 'destination')
            ->create();

        // Solicitado dentro do range (status errado)
        Order::factory()
            ->for($user)
            ->withDates('2027-07-15', '2027-07-25')
            ->has(Destination::factory(), 'destination')
            ->create();

        $response = $this->getJson(
            '/pedido?status=' . OrderStatusEnum::Approved->value . '&data_inicio=10-07-2027&data_fim=20-07-2027',
            $this->authHeaders($user),
        );

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Aprovado', $data[0]['status']);
    }

    // -------------------------------------------------------------------------
    // Teste sem filtros = comportamento atual (todos do user)
    // -------------------------------------------------------------------------

    public function test_sem_filtros_retorna_todos_pedidos_do_usuario(): void
    {
        $user = User::factory()->create();

        Order::factory()
            ->for($user)
            ->approved()
            ->has(Destination::factory(), 'destination')
            ->create();

        Order::factory()
            ->for($user)
            ->cancelled()
            ->has(Destination::factory(), 'destination')
            ->create();

        $this->createOrdersForUser($user, 1);

        $response = $this->getJson('/pedido', $this->authHeaders($user));

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Testes de validação (422)
    // -------------------------------------------------------------------------

    public function test_422_status_invalido(): void
    {
        $user = User::factory()->create();

        $this->getJson('/pedido?status=99', $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_422_data_inicio_formato_invalido(): void
    {
        $user = User::factory()->create();

        // Formato Y-m-d é inválido para o campo que espera d-m-Y
        $this->getJson('/pedido?data_inicio=2026-12-01', $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_inicio']);
    }

    public function test_422_data_fim_anterior_a_data_inicio(): void
    {
        $user = User::factory()->create();

        $this->getJson('/pedido?data_inicio=20-07-2027&data_fim=10-07-2027', $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_fim']);
    }

    // -------------------------------------------------------------------------
    // Teste de isolamento com filtros
    // -------------------------------------------------------------------------

    public function test_filtros_respeitam_isolamento_por_usuario(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // user2 tem pedidos Aprovados no range — user1 não deve vê-los
        Order::factory()
            ->for($user2)
            ->approved()
            ->withDates('2027-07-12', '2027-07-22')
            ->has(Destination::factory(), 'destination')
            ->count(3)
            ->create();

        // user1 tem 1 pedido Solicitado no range
        Order::factory()
            ->for($user1)
            ->withDates('2027-07-15', '2027-07-25')
            ->has(Destination::factory(), 'destination')
            ->create();

        // user1 filtra por Aprovado no range — deve ver 0 (os aprovados são do user2)
        $response = $this->getJson(
            '/pedido?status=' . OrderStatusEnum::Approved->value . '&data_inicio=10-07-2027&data_fim=20-07-2027',
            $this->authHeaders($user1),
        );

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Testes de admin — visibilidade global
    // -------------------------------------------------------------------------

    public function test_admin_lista_pedidos_de_todos_os_usuarios(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->createOrdersForUser($user1, 2);
        $this->createOrdersForUser($user2, 3);

        $response = $this->getJson('/pedido', $this->authHeaders($admin));

        $response->assertStatus(200);
        // Admin deve enxergar os 5 pedidos combinados
        $this->assertCount(5, $response->json('data'));
    }

    public function test_admin_com_filtro_status_aplica_sobre_escopo_global(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // user1: 1 Aprovado
        Order::factory()
            ->for($user1)
            ->approved()
            ->has(Destination::factory(), 'destination')
            ->create();

        // user2: 2 Aprovados
        Order::factory()
            ->for($user2)
            ->approved()
            ->has(Destination::factory(), 'destination')
            ->count(2)
            ->create();

        // user1: 1 Solicitado (não deve aparecer no filtro)
        $this->createOrdersForUser($user1, 1);

        $response = $this->getJson(
            '/pedido?status=' . OrderStatusEnum::Approved->value,
            $this->authHeaders($admin),
        );

        $response->assertStatus(200);
        // 3 aprovados no total (1 de user1 + 2 de user2)
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_com_filtro_data_aplica_sobre_escopo_global(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Dentro do range
        $this->createOrderWithDates($user1, '2027-07-12', '2027-07-22');
        $this->createOrderWithDates($user2, '2027-07-15', '2027-07-25');

        // Fora do range
        $this->createOrderWithDates($user1, '2027-08-01', '2027-08-10');

        $response = $this->getJson(
            '/pedido?data_inicio=10-07-2027&data_fim=20-07-2027',
            $this->authHeaders($admin),
        );

        $response->assertStatus(200);
        // Apenas os 2 pedidos dentro do range, independente do usuário dono
        $this->assertCount(2, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Testes de autenticação / headers
    // -------------------------------------------------------------------------

    public function test_401_sem_jwt(): void
    {
        $this->getJson('/pedido', $this->requestIdHeader())
            ->assertStatus(401);
    }

    public function test_400_sem_request_id(): void
    {
        $this->getJson('/pedido')
            ->assertStatus(400);
    }
}
