<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatedRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('X-Request-Id')) {
            return response()->json(
                ['message' => 'Header X-Request-Id é obrigatório.'],
                400, // Bad Request: a requisição está malformada
            );
        }

        return $next($request);
    }
}
