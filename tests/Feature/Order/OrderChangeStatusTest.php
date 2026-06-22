<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderChangeStatusTest extends TestCase
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
        return Order::factory()
            ->for($user)
            ->has(\App\Models\Destination::factory(), 'destination')
            ->create();
    }

    // -------------------------------------------------------------------------
    // Testes de sucesso
    // -------------------------------------------------------------------------

    public function test_admin_aprova_pedido_de_outro_usuario_retorna_200(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            $this->authHeaders($admin),
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Aprovado');

        $this->assertDatabaseHas('orders', [
            'ref' => $order->ref,
            'status_id' => OrderStatusEnum::Approved->value,
        ]);

        Notification::assertSentTo($regular, OrderStatusChangedNotification::class);
    }

    public function test_admin_cancela_pedido_solicitado_de_outro_usuario_retorna_200(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        // Pedido criado com status padrão Registred (Solicitado)
        $order = $this->createOrderWithDestination($regular);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Cancelled->value],
            $this->authHeaders($admin),
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Cancelado');

        Notification::assertSentTo($regular, OrderStatusChangedNotification::class);
    }

    // -------------------------------------------------------------------------
    // Testes da regra de cancelamento (409)
    // -------------------------------------------------------------------------

    public function test_409_cancelar_pedido_ja_aprovado(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        // Coloca o pedido em status Aprovado diretamente no banco
        $order->update(['status_id' => OrderStatusEnum::Approved->value]);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Cancelled->value],
            $this->authHeaders($admin),
        );

        $response->assertStatus(409)
            ->assertJsonFragment(['error' => 'Não é possível cancelar um pedido que já foi aprovado.']);

        // Status permanece Approved
        $this->assertDatabaseHas('orders', [
            'ref' => $order->ref,
            'status_id' => OrderStatusEnum::Approved->value,
        ]);

        // Bloqueio impede notificação
        Notification::assertNothingSent();
    }

    public function test_aprovar_pedido_solicitado_envia_notificacao(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            $this->authHeaders($admin),
        )->assertStatus(200);

        Notification::assertSentTo($regular, OrderStatusChangedNotification::class);
    }

    public function test_cancelar_pedido_solicitado_envia_notificacao(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Cancelled->value],
            $this->authHeaders($admin),
        )->assertStatus(200);

        Notification::assertSentTo($regular, OrderStatusChangedNotification::class);
    }

    // -------------------------------------------------------------------------
    // Testes de idempotência (sem re-notificação)
    // -------------------------------------------------------------------------

    public function test_aprovar_pedido_ja_aprovado_nao_reenvia_notificacao(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);
        $order->update(['status_id' => OrderStatusEnum::Approved->value]);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            $this->authHeaders($admin),
        );

        $response->assertStatus(200);
        Notification::assertNothingSent();
    }

    public function test_cancelar_pedido_ja_cancelado_nao_reenvia_notificacao(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);
        $order->update(['status_id' => OrderStatusEnum::Cancelled->value]);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Cancelled->value],
            $this->authHeaders($admin),
        );

        $response->assertStatus(200);
        Notification::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // Testes de autorização (403)
    // -------------------------------------------------------------------------

    public function test_403_admin_altera_proprio_pedido(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrderWithDestination($admin);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            $this->authHeaders($admin),
        );

        $response->assertStatus(403)
            ->assertJsonFragment(['error' => 'O solicitante não pode alterar o status do próprio pedido.']);
    }

    public function test_403_usuario_nao_admin_recebe_mensagem_do_middleware(): void
    {
        $regular = User::factory()->create();
        $owner = User::factory()->create();
        $order = $this->createOrderWithDestination($owner);

        $response = $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            $this->authHeaders($regular),
        );

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Acesso restrito a administradores.']);
    }

    // -------------------------------------------------------------------------
    // Testes de 404
    // -------------------------------------------------------------------------

    public function test_404_referencia_inexistente(): void
    {
        $admin = User::factory()->admin()->create();

        $this->patchJson(
            '/pedido/ref-nao-existe/status',
            ['status' => OrderStatusEnum::Approved->value],
            $this->authHeaders($admin),
        )->assertStatus(404)
            ->assertJsonFragment(['error' => 'Pedido não encontrado']);
    }

    // -------------------------------------------------------------------------
    // Testes de validação (422)
    // -------------------------------------------------------------------------

    public function test_422_status_ausente(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        $this->patchJson(
            "/pedido/{$order->ref}/status",
            [],
            $this->authHeaders($admin),
        )->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_422_status_registred_nao_permitido(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        // status=1 (Registred) não é permitido na mudança; apenas 2 e 3
        $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Registred->value],
            $this->authHeaders($admin),
        )->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // -------------------------------------------------------------------------
    // Testes de autenticação / headers
    // -------------------------------------------------------------------------

    public function test_401_sem_jwt(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrderWithDestination($owner);

        $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            $this->requestIdHeader(),
        )->assertStatus(401);
    }

    public function test_400_sem_request_id(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();
        $order = $this->createOrderWithDestination($regular);

        $this->patchJson(
            "/pedido/{$order->ref}/status",
            ['status' => OrderStatusEnum::Approved->value],
            ['Authorization' => 'Bearer ' . app(\App\Services\JwtService::class)->encode($admin)],
        )->assertStatus(400);
    }
}
