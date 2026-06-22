<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\DTO\User\RegisteredUserDTO
 */
class RegisteredUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'api_key' => $this->api_key,
            'criado_em' => $this->criado_em,
        ];
    }
}
