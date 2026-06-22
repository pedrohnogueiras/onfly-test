<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\OrderStatusEnum;
use App\Models\Destination;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use stdClass;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrderStatusSeeder::class);
    }

    public function test_via_retorna_mail_e_database(): void
    {
        $order = Order::factory()
            ->for(User::factory()->create())
            ->has(Destination::factory(), 'destination')
            ->create(['status_id' => OrderStatusEnum::Approved->value]);

        $notification = new OrderStatusChangedNotification($order);

        $this->assertSame(['mail', 'database'], $notification->via(new stdClass()));
    }

    public function test_to_array_contem_campos_esperados_para_aprovado(): void
    {
        $regular = User::factory()->create();
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create(['status_id' => OrderStatusEnum::Approved->value]);

        $notification = new OrderStatusChangedNotification($order);
        $payload = $notification->toArray($regular);

        $this->assertSame($order->ref, $payload['pedido_ref']);
        $this->assertSame(OrderStatusEnum::Approved->value, $payload['status_id']);
        $this->assertSame('Aprovado', $payload['status_descricao']);
        $this->assertArrayHasKey('mensagem', $payload);
    }

    public function test_to_array_contem_campos_esperados_para_cancelado(): void
    {
        $regular = User::factory()->create();
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create(['status_id' => OrderStatusEnum::Cancelled->value]);

        $notification = new OrderStatusChangedNotification($order);
        $payload = $notification->toArray($regular);

        $this->assertSame($order->ref, $payload['pedido_ref']);
        $this->assertSame(OrderStatusEnum::Cancelled->value, $payload['status_id']);
        $this->assertSame('Cancelado', $payload['status_descricao']);
    }

    public function test_to_mail_pedido_aprovado_contem_assunto_e_referencia(): void
    {
        $regular = User::factory()->create(['name' => 'Fulano']);
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create(['status_id' => OrderStatusEnum::Approved->value]);

        $notification = new OrderStatusChangedNotification($order);
        $mail = $notification->toMail($regular);

        $this->assertStringContainsString($order->ref, $mail->subject);
        $this->assertStringContainsString('Aprovado', $mail->subject);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString($order->ref, $introLines);
        $this->assertStringContainsString('Aprovado', $introLines);
    }

    public function test_to_mail_pedido_cancelado_contem_assunto_e_referencia(): void
    {
        $regular = User::factory()->create(['name' => 'Ciclana']);
        $order = Order::factory()
            ->for($regular)
            ->has(Destination::factory(), 'destination')
            ->create(['status_id' => OrderStatusEnum::Cancelled->value]);

        $notification = new OrderStatusChangedNotification($order);
        $mail = $notification->toMail($regular);

        $this->assertStringContainsString($order->ref, $mail->subject);
        $this->assertStringContainsString('Cancelado', $mail->subject);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString($order->ref, $introLines);
        $this->assertStringContainsString('Cancelado', $introLines);
    }
}
