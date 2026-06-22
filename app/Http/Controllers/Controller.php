<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Onfly API',
    description: 'API de gestão de pedidos Onfly.',
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Ambiente local',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token JWT obtido em POST /auth/token.',
)]
#[OA\Schema(
    schema: 'RespostaErro',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Mensagem de erro.'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'RespostaToken',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                new OA\Property(property: 'expires_in', type: 'string', example: '3600'),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'RespostaUsuarioCadastrado',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'ref', type: 'string', format: 'uuid', example: '9d4e3c2a-...'),
                new OA\Property(property: 'name', type: 'string', example: 'João Silva'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@exemplo.com'),
                new OA\Property(property: 'is_admin', type: 'boolean', example: false),
                new OA\Property(property: 'api_key', type: 'string', description: 'Exibida apenas neste momento.', example: 'abc123xyz...'),
                new OA\Property(property: 'criado_em', type: 'string', format: 'date-time', example: '2024-01-15 10:30:00'),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Destino',
    properties: [
        new OA\Property(property: 'cidade', type: 'string', example: 'São Paulo'),
        new OA\Property(property: 'estado', type: 'string', example: 'SP'),
        new OA\Property(property: 'pais', type: 'string', example: 'Brasil'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Pedido',
    properties: [
        new OA\Property(property: 'ref', type: 'string', format: 'uuid', example: '9d4e3c2a-...'),
        new OA\Property(property: 'solicitante', type: 'string', example: 'João Silva'),
        new OA\Property(property: 'data_partida', type: 'string', example: '15-01-2025', description: 'Formato DD-MM-YYYY'),
        new OA\Property(property: 'data_retorno', type: 'string', example: '20-01-2025', description: 'Formato DD-MM-YYYY'),
        new OA\Property(property: 'status', type: 'string', example: 'Solicitado', enum: ['Solicitado', 'Aprovado', 'Cancelado']),
        new OA\Property(property: 'destino', ref: '#/components/schemas/Destino'),
        new OA\Property(property: 'criado_em', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'RespostaPedido',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Pedido'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'RespostaListaPedidos',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Pedido'),
        ),
    ],
    type: 'object',
)]
abstract class Controller
{
    //
}
