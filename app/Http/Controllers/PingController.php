<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * Documentação OpenAPI para o endpoint de health-check.
 * A rota real é definida como closure em routes/api.php.
 */
class PingController extends Controller
{
    #[OA\Get(
        path: '/ping',
        summary: 'Health-check da API',
        description: 'Verifica se a API está disponível.',
        tags: ['Saúde'],
        parameters: [
            new OA\Parameter(
                name: 'X-Request-Id',
                in: 'header',
                required: true,
                description: 'Identificador único da requisição (UUID).',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API disponível.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Olá!!! =D'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Header X-Request-Id ausente.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaErro'),
            ),
        ],
    )]
    public function __invoke(): void
    {
        // Apenas para hospedar a anotação OpenAPI.
        // A rota real usa closure em routes/api.php.
    }
}
