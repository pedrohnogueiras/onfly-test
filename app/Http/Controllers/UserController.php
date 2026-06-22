<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\User\RegisterRequestDTO;
use App\Http\Requests\User\UserRegisterRequest;
use App\Http\Resources\RegisteredUserResource;
use App\Logging\RequestLogger;
use App\Services\UserService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserController extends Controller
{
    public const CONTEXT = 'USER';

    public function __construct(
        private readonly UserService $userService,
        private readonly RequestLogger $logger,
    ) {
    }

    #[OA\Post(
        path: '/usuario',
        summary: 'Cadastrar novo usuário',
        description: 'Cria um novo usuário e retorna os dados com a api_key, exibida apenas neste momento.',
        tags: ['Usuários'],
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
                required: ['nome', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'João Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'joao@exemplo.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'senha1234'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', minLength: 8, example: 'senha1234'),
                    new OA\Property(property: 'is_admin', type: 'boolean', example: false, description: 'Define se o usuário terá perfil administrador. Opcional, padrão false.'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuário cadastrado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuarioCadastrado'),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 409,
                description: 'E-mail já cadastrado.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de entrada inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
    )]
    public function store(UserRegisterRequest $request): JsonResponse
    {
        $this->logger->setContext([
            'context' => self::CONTEXT,
            'request_id' => $request->headers->get('X-Request-Id'),
            'user_ref' => null,
        ]);

        try {
            $registerData = RegisterRequestDTO::from($request->validated());

            $registeredUser = $this->userService->register($registerData);

            return RegisteredUserResource::make($registeredUser)
                ->response()
                ->setStatusCode(201);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                $this->logger->error(
                    __FUNCTION__,
                    $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
                );

                return response()->json([
                    'error' => 'O e-mail informado já está cadastrado.',
                ], 409);
            }

            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro ao cadastrar usuário',
            ], 500);
        } catch (Throwable $e) {
            $this->logger->error(
                __FUNCTION__,
                $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            );

            return response()->json([
                'error' => 'Erro interno, tente novamente mais tarde!',
            ], 500);
        }
    }

    /**
     * Detecta violação de constraint UNIQUE (SQLSTATE 23000 / error code 1062).
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->errorInfo[0] === '23000'
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
