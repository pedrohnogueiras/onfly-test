<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Order\Request\OrderFilterDTO;
use App\DTO\Order\Request\OrderRequestDTO;
use App\Logging\RequestLogger;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

    public function getByReference(string $reference, int $userId): Order
    {
        $this->logger->info(__FUNCTION__, 'buscando pedido por referência');

        return Order::query()
            ->with('destination', 'orderStatus')
            ->where('user_id', $userId)
            ->where('ref', $reference)
            ->firstOrFail();
    }

    public function listByUser(int $userId, OrderFilterDTO $filters): Collection
    {
        $this->logger->info(__FUNCTION__, 'listando pedidos do usuário');

        return Order::query()
            ->with('destination', 'orderStatus')
            ->where('user_id', $userId)
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

    public function changeStatus(string $reference, int $requesterId, int $status): Order
    {
        $this->logger->info(__FUNCTION__, 'buscando pedido para alteração de status');
        $order = Order::query()
            ->where('ref', $reference)
            ->firstOrFail();

        if ($order->user_id === $requesterId) {
            throw new AuthorizationException('O solicitante não pode alterar o status do próprio pedido.');
        }

        $this->logger->info(__FUNCTION__, 'alterando status do pedido');
        $order->status_id = $status;
        $order->save();

        return $order->load('destination', 'orderStatus');
    }
}
