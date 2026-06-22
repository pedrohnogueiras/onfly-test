<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Order\Request\OrderFilterDTO;
use App\DTO\Order\Request\OrderRequestDTO;
use App\Enums\OrderStatusEnum;
use App\Exceptions\OrderStatusTransitionException;
use App\Logging\RequestLogger;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderService
{
    public function __construct(
        private readonly RequestLogger $logger,
    ) {
    }

    public function create(OrderRequestDTO $data): Order
    {

        return DB::transaction(function () use ($data) {

            $this->logger->info(__FUNCTION__, 'cadastrando pedido');
            $order = Order::create($data->except('destination')->toArray());

            $this->logger->info(__FUNCTION__, 'cadastrando destino');
            $order->destination()->create($data->orderDestination->toArray());

            return $order;
        });
    }

    public function getByReference(string $reference, User $user): Order
    {
        $this->logger->info(__FUNCTION__, 'buscando pedido por referência');

        $query = Order::query()
            ->with('destination', 'orderStatus');

        $this->scopeToUser($query, $user);

        return $query
            ->where('ref', $reference)
            ->firstOrFail();
    }

    public function listForUser(User $user, OrderFilterDTO $filters): Collection
    {
        $this->logger->info(__FUNCTION__, 'listando pedidos do usuário');

        $query = Order::query()
            ->with('destination', 'orderStatus');

        $this->scopeToUser($query, $user);

        return $query
            ->when($filters->status !== null, function ($query) use ($filters) {
                $query->where('status_id', $filters->status->value);
            })
            ->when($filters->dataInicio !== null && $filters->dataFim !== null, function ($query) use ($filters) {
                $query->whereBetween('departure_date', [
                    $filters->dataInicio->format('Y-m-d'),
                    $filters->dataFim->format('Y-m-d'),
                ]);
            })
            ->when($filters->dataInicio !== null && $filters->dataFim === null, function ($query) use ($filters) {
                $query->whereDate('departure_date', '>=', $filters->dataInicio->format('Y-m-d'));
            })
            ->when($filters->dataFim !== null && $filters->dataInicio === null, function ($query) use ($filters) {
                $query->whereDate('departure_date', '<=', $filters->dataFim->format('Y-m-d'));
            })
            ->latest()
            ->get();
    }

    /**
     * Aplica restrição de dono à query: não-admin vê apenas os próprios pedidos;
     * admin enxerga pedidos de todos os usuários.
     *
     * @param  Builder<Order>  $query
     */
    private function scopeToUser(Builder $query, User $user): void
    {
        if (!$user->is_admin) {
            $query->where('user_id', $user->id);
        }
    }

    public function changeStatus(string $reference, int $requesterId, int $status): Order
    {
        $this->logger->info(__FUNCTION__, 'buscando pedido para alteração de status');
        $order = Order::query()
            ->where('ref', $reference)
            ->firstOrFail();

        if ($order->user_id === $requesterId) {
            throw new AuthorizationException('O solicitante não pode alterar o status do próprio pedido.');
        }

        if ($status === OrderStatusEnum::Cancelled->value && $order->status_id === OrderStatusEnum::Approved->value) {
            throw new OrderStatusTransitionException('Não é possível cancelar um pedido que já foi aprovado.');
        }

        $this->logger->info(__FUNCTION__, 'alterando status do pedido');
        $mudou = $order->status_id !== $status;
        $order->status_id = $status;
        $order->save();

        if ($mudou && in_array($status, [OrderStatusEnum::Approved->value, OrderStatusEnum::Cancelled->value], true)) {
            $order->loadMissing('user');

            if ($order->user) {
                try {
                    $order->user->notify(new OrderStatusChangedNotification($order));
                } catch (\Throwable $e) {
                    $this->logger->error(__FUNCTION__, 'falha ao enviar notificação de mudança de status: ' . $e->getMessage());
                }
            }
        }

        return $order->load('destination', 'orderStatus');
    }
}
