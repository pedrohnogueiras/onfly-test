<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Order\Request\OrderFilterDTO;
use App\DTO\Order\Request\OrderRequestDTO;
use App\Exceptions\OrderStatusTransitionException;
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
use OpenApi\Attributes as OA;
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

    #[OA\Post(
        path: '/pedido',
        summary: 'Criar pedido de viagem',
        description: 'Cria um novo pedido de viagem para o usuário.',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'X-Request-Id',
                in: 'header',
                required: true,
                description: 'Identificador único da requisição (UUID).',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['solicitante', 'data_partida', 'data_retorno', 'destino'],
                properties: [
                    new OA\Property(property: 'solicitante', type: 'string', description: 'Nome do solicitante da viagem.', example: 'João Silva'),
                    new OA\Property(property: 'data_partida', type: 'string', description: 'Data de partida no formato DD-MM-YYYY.', example: '15-01-2025'),
                    new OA\Property(property: 'data_retorno', type: 'string', description: 'Data de retorno no formato DD-MM-YYYY. Deve ser igual ou posterior à data de partida.', example: '20-01-2025'),
                    new OA\Property(
                        property: 'destino',
                        description: 'Dados do destino da viagem.',
                        properties: [
                            new OA\Property(property: 'cidade', type: 'string', example: 'São Paulo'),
                            new OA\Property(property: 'estado', type: 'string', example: 'SP'),
                            new OA\Property(property: 'pais', type: 'string', example: 'Brasil'),
                        ],
                        type: 'object',
                    ),
                    new OA\Property(property: 'status', type: 'integer', description: 'Status inicial do pedido. Opcional. 1=Solicitado, 2=Aprovado, 3=Cancelado.', enum: [1, 2, 3], example: 1),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pedido criado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaPedido'),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 401,
                description: 'Não autenticado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de entrada inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
        security: [['bearerAuth' => []]],
    )]
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

    #[OA\Get(
        path: '/pedido/{referencia_pedido}',
        summary: 'Buscar pedido por referência',
        description: 'Retorna os dados de um pedido específico pelo seu identificador único.',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'X-Request-Id',
                in: 'header',
                required: true,
                description: 'Identificador único da requisição (UUID).',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
            new OA\Parameter(
                name: 'referencia_pedido',
                in: 'path',
                required: true,
                description: 'Referência UUID do pedido.',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pedido encontrado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaPedido'),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 401,
                description: 'Não autenticado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 404,
                description: 'Pedido não encontrado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
        security: [['bearerAuth' => []]],
    )]
    public function index(OrderIndexRequest $request, string $referencia_pedido)
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => $request->user()->ref,
        ]);

        try {

            $this->logger->info(__FUNCTION__, 'Buscando pedido');

            $order = $this->orderService->getByReference($referencia_pedido, $request->user());

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

    #[OA\Patch(
        path: '/pedido/{referencia_pedido}/status',
        summary: 'Alterar status de um pedido',
        description: 'Altera o status de um pedido existente. Requer perfil administrador. Apenas os status 2 (Aprovado) e 3 (Cancelado) são aceitos.',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'X-Request-Id',
                in: 'header',
                required: true,
                description: 'Identificador único da requisição (UUID).',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
            new OA\Parameter(
                name: 'referencia_pedido',
                in: 'path',
                required: true,
                description: 'Referência UUID do pedido.',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(
                        property: 'status',
                        type: 'integer',
                        description: '2 = Aprovado, 3 = Cancelado.',
                        enum: [2, 3],
                        example: 2,
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status alterado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaPedido'),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 401,
                description: 'Não autenticado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 403,
                description: 'Acesso negado. Apenas administradores podem alterar status.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 404,
                description: 'Pedido não encontrado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 409,
                description: 'Transição de status inválida (ex: cancelar pedido já aprovado).',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de entrada inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
        security: [['bearerAuth' => []]],
    )]
    public function changeStatus(OrderStatusRequest $request, string $referencia_pedido)
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => $request->user()->ref,
        ]);

        try {

            $this->logger->info(__FUNCTION__, 'Alterando status do pedido');

            $order = $this->orderService->changeStatus(
                $referencia_pedido,
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

        } catch (OrderStatusTransitionException $e) {

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => $e->getMessage(),
            ], 409);

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

    #[OA\Get(
        path: '/pedido',
        summary: 'Listar pedidos de viagem',
        description: 'Lista os pedidos do usuário autenticado com filtros opcionais por status e período.',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'X-Request-Id',
                in: 'header',
                required: true,
                description: 'Identificador único da requisição (UUID).',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filtrar por status do pedido. 1=Solicitado, 2=Aprovado, 3=Cancelado.',
                schema: new OA\Schema(type: 'integer', enum: [1, 2, 3]),
            ),
            new OA\Parameter(
                name: 'data_inicio',
                in: 'query',
                required: false,
                description: 'Data de início do filtro no formato DD-MM-YYYY.',
                schema: new OA\Schema(type: 'string', example: '01-01-2025'),
            ),
            new OA\Parameter(
                name: 'data_fim',
                in: 'query',
                required: false,
                description: 'Data de fim do filtro no formato DD-MM-YYYY. Deve ser igual ou posterior a data_inicio.',
                schema: new OA\Schema(type: 'string', example: '31-01-2025'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de pedidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaListaPedidos'),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 401,
                description: 'Não autenticado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
        security: [['bearerAuth' => []]],
    )]
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
            $orders = $this->orderService->listForUser($request->user(), $filters);

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
