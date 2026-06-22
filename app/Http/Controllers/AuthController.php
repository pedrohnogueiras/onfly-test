<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Auth\TokenRequestDTO;
use App\Http\Requests\Token\TokenRequest;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    #[OA\Post(
        path: '/auth/token',
        summary: 'Gerar token de acesso JWT',
        description: 'Autentica o usuário via x_api_key e retorna um token JWT.',
        tags: ['Autenticação'],
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
                required: ['x_api_key'],
                properties: [
                    new OA\Property(
                        property: 'x_api_key',
                        type: 'string',
                        description: 'Chave de API do usuário.',
                        example: 'abc123xyz...',
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token gerado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaToken'),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 401,
                description: 'Credencial inválida.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados de entrada inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
    )]
    public function auth(TokenRequest $request): JsonResponse
    {
        try {
            $dataRequest = $request->validated();

            $data = $this->authService->authenticate(TokenRequestDTO::from($dataRequest));
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Erro interno, tente novamente mais tarde!',
            ], 500);
        }

        return response()->json($data);
    }
}
