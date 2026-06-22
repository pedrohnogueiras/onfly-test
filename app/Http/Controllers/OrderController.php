<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Order\Request\OrderFilterDTO;
use App\DTO\Order\Request\OrderRequestDTO;
use App\Http\Requests\Order\OrderIndexRequest;
use App\Http\Requests\Order\OrderListRequest;
use App\Http\Requests\Order\OrderStatusRequest;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Logging\RequestLogger;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderController extends Controller
{
    public const CONTEXT = 'ORDER';
    public function __construct(
        private readonly OrderService $orderService,
        private readonly RequestLogger $logger,
    ) {
    }

    public function store(OrderStoreRequest $request)
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => $request->user()->ref,
        ]);

        try {

            $this->logger->info(__FUNCTION__, 'Iniciando fluxo de pedido');

            $orderData = OrderRequestDTO::from([
                'user_id' => $request->user()->id,
                ...$request->validated(),
            ]);

            $order = $this->orderService->create($orderData);

            $order->load('destination', 'orderStatus');

            return OrderResource::make($order)
                ->response()
                ->setStatusCode(201);

        } catch (QueryException $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro ao cadastrar pedido',
            ], 500);

        } catch (Throwable $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro interno tente novamente mais tarde!',
            ], 500);
        }
    }
    public function index(OrderIndexRequest $request, string $order_reference)
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => $request->user()->ref,
        ]);

        try {

            $this->logger->info(__FUNCTION__, 'Buscando pedido');

            $order = $this->orderService->getByReference($order_reference, $request->user()->id);

            return OrderResource::make($order);

        } catch (ModelNotFoundException $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Pedido não encontrado',
            ], 404);

        } catch (Throwable $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro interno tente novamente mais tarde!',
            ], 500);
        }
    }

    public function changeStatus(OrderStatusRequest $request, string $order_reference)
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => $request->user()->ref,
        ]);

        try {

            $this->logger->info(__FUNCTION__, 'Alterando status do pedido');

            $order = $this->orderService->changeStatus(
                $order_reference,
                $request->user()->id,
                (int) $request->validated('status'),
            );

            return OrderResource::make($order);

        } catch (ModelNotFoundException $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Pedido não encontrado',
            ], 404);

        } catch (AuthorizationException $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => $e->getMessage(),
            ], 403);

        } catch (Throwable $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro interno tente novamente mais tarde!',
            ], 500);
        }
    }

    public function list(OrderListRequest $request)
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => $request->user()->ref,
        ]);

        try {

            $this->logger->info(__FUNCTION__, 'Listando pedidos');

            $filters = OrderFilterDTO::from($request->validated());
            $orders = $this->orderService->listByUser($request->user()->id, $filters);

            return OrderResource::collection($orders);

        } catch (Throwable $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro interno tente novamente mais tarde!',
            ], 500);
        }
    }
}
