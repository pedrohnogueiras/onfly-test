<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Auth\TokenRequestDTO;
use App\Http\Requests\Token\TokenRequest;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

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
