<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Order\Request\OrderFilterDTO;
use App\DTO\Order\Request\OrderRequestDTO;
use App\Enums\OrderStatusEnum;
use App\Exceptions\OrderStatusTransitionException;
use App\Models\Destination;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\OrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrderStatusSeeder::class);
    }

    private function service(): OrderService
    {
        return app(OrderService::class);
    }

    /**
     * Constrói um OrderRequestDTO completo para um user.
     */
    private function makeOrderRequestDTO(User $user): OrderRequestDTO
    {
        return OrderRequestDTO::from([
            'user_id' => $user->id,
            'solicitante' => 'Fulano de Tal',
            'data_partida' => '15-08-2027',
            'data_retorno' => '25-08-2027',
            'destino' => [
                'cidade' => 'Rio de Janeiro',
                'estado' => 'RJ',
                'pais' => 'Brasil',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_persiste_order_no_banco(): void
    {
        $user = User::factory()->create();
        $dto = $this->makeOrderRequestDTO($user);

        $order = $this->service()->create($dto);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'applicant' => 'Fulano de Tal',
            'departure_date' => '2027-08-15',
            'return_date' => '2027-08-25',
            'status_id' => OrderStatusEnum::Registred->value,
        ]);
    }

    public function test_create_persiste_destination_no_banco(): void
    {
        $user = User::factory()->create();
        $dto = $this->makeOrderRequestDTO($user);

        $order = $this->service()->create($dto);

        $this->assertDatabaseHas('destinations', [
            'order_id' => $order->id,
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'country' => 'Brasil',
        ]);
    }

    public function test_create_gera_ref_com_prefixo_ped(): void
    {
        $user = User::factory()->create();
        $order = $this->service()->create($this->makeOrderRequestDTO($user));

        $this->assertStringStartsWith(Order::PREFIX_EXTERNAL_REFERENCE . '-', $order->ref);
    }

    // -------------------------------------------------------------------------
    // getByReference
    // -------------------------------------------------------------------------

    public function test_get_by_reference_retorna_order_do_usuario_correto(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()
            ->for($user)
            ->has(Destination::factory(), 'destination')
            ->create();

        $found = $this->service()->getByReference($order->ref, $user);

        $this->assertSame($order->id, $found->id);
    }

    public function test_get_by_reference_lanca_not_found_para_outro_usuario(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()
            ->for($owner)
            ->has(Destination::factory(), 'destination')
            ->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service()->getByReference($order->ref, $other);
    }

    public function test_get_by_reference_lanca_not_found_para_ref_inexistente(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service()->getByReference('ref-inexistente-xyz', $user);
    }

    public function test_get_by_reference_admin_acessa_pedido_de_outro_usuario(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $order = Order::factory()
            ->for($owner)
            ->has(Destination::factory(), 'destination')
            ->create();

        $found = $this->service()->getByReference($order->ref, $admin);

        $this->assertSame($order->id, $found->id);
    }

    // -------------------------------------------------------------------------
    // listForUser
    // -------------------------------------------------------------------------

    public function test_list_for_user_retorna_somente_pedidos_do_usuario(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::factory()->count(3)->for($user1)->has(Destination::factory(), 'destination')->create();
        Order::factory()->count(2)->for($user2)->has(Destination::factory(), 'destination')->create();

        $filters = new OrderFilterDTO(status: null, dataInicio: null, dataFim: null);
        $result = $this->service()->listForUser($user1, $filters);

        $this->assertCount(3, $result);
        $result->each(fn (Order $o) => $this->assertSame($user1->id, $o->user_id));
    }

    public function test_list_for_user_retorna_colecao_vazia_quando_sem_pedidos(): void
    {
        $user = User::factory()->create();

        $filters = new OrderFilterDTO(status: null, dataInicio: null, dataFim: null);
        $result = $this->service()->listForUser($user, $filters);

        $this->assertCount(0, $result);
    }

    public function test_list_for_user_admin_retorna_pedidos_de_todos_os_usuarios(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::factory()->count(3)->for($user1)->has(Destination::factory(), 'destination')->create();
        Order::factory()->count(2)->for($user2)->has(Destination::factory(), 'destination')->create();

        $filters = new OrderFilterDTO(status: null, dataInicio: null, dataFim: null);
        $result = $this->service()->listForUser($admin, $filters);

        $this->assertCount(5, $result);
    }

    // -------------------------------------------------------------------------
    // changeStatus
    // -------------------------------------------------------------------------

    public function test_change_status_atualiza_status_do_pedido(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create();

        $updated = $this->service()->changeStatus($order->ref, $admin->id, OrderStatusEnum::Approved->value);

        $this->assertSame(OrderStatusEnum::Approved->value, $updated->status_id);
        $this->assertDatabaseHas('orders', [
            'ref' => $order->ref,
            'status_id' => OrderStatusEnum::Approved->value,
        ]);
    }

    public function test_change_status_lanca_authorization_exception_quando_requester_e_dono(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()
            ->for($user)
            ->has(Destination::factory(), 'destination')
            ->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('O solicitante não pode alterar o status do próprio pedido.');

        $this->service()->changeStatus($order->ref, $user->id, OrderStatusEnum::Approved->value);
    }

    public function test_change_status_lanca_not_found_para_ref_inexistente(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service()->changeStatus('ref-inexistente-xyz', $user->id, OrderStatusEnum::Approved->value);
    }

    public function test_change_status_lanca_exception_ao_cancelar_pedido_aprovado(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create(['status_id' => OrderStatusEnum::Approved->value]);

        $this->expectException(OrderStatusTransitionException::class);
        $this->expectExceptionMessage('Não é possível cancelar um pedido que já foi aprovado.');

        $this->service()->changeStatus($order->ref, $admin->id, OrderStatusEnum::Cancelled->value);
    }

    public function test_change_status_dispara_notificacao_ao_aprovar(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create();

        $this->service()->changeStatus($order->ref, $admin->id, OrderStatusEnum::Approved->value);

        Notification::assertSentTo($regular, OrderStatusChangedNotification::class);
    }

    public function test_change_status_dispara_notificacao_ao_cancelar_pedido_solicitado(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create();

        $this->service()->changeStatus($order->ref, $admin->id, OrderStatusEnum::Cancelled->value);

        Notification::assertSentTo($regular, OrderStatusChangedNotification::class);
    }
}
