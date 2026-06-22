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
