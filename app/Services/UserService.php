<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\User\RegisteredUserDTO;
use App\DTO\User\RegisterRequestDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Registra um novo usuário e retorna seus dados acompanhados da api_key
     * em texto puro, exibida uma única vez.
     *
     * O campo is_admin é definido a partir do payload recebido (via RegisterRequestDTO),
     * sendo false por padrão quando omitido.
     */
    public function register(RegisterRequestDTO $registerData): RegisteredUserDTO
    {
        return DB::transaction(function () use ($registerData): RegisteredUserDTO {
            $user = User::create([
                'name' => $registerData->name,
                'email' => $registerData->email,
                'is_admin' => $registerData->isAdmin,
                'password' => Hash::make($registerData->password),
            ]);

            $apiKey = $user->generateApiKey();

            return RegisteredUserDTO::fromUser($user, $apiKey);
        });
    }
}
