<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class JwtAuthenticate
{
    public function __construct(private JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token não fornecido.'], 401);
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        $user = User::where('ref', $payload->sub)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
