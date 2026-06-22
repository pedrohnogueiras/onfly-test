<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\User\RegisteredUserDTO;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class RegisteredUserDTOTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_user_mapeia_atributos_e_api_key(): void
    {
        $user = User::factory()->create([
            'name' => 'Ciclano',
            'email' => 'ciclano@onfly.com.br',
            'is_admin' => false,
        ]);

        $dto = RegisteredUserDTO::fromUser($user, 'minha-api-key-plana');

        $this->assertSame($user->ref, $dto->ref);
        $this->assertSame('Ciclano', $dto->name);
        $this->assertSame('ciclano@onfly.com.br', $dto->email);
        $this->assertFalse($dto->is_admin);
        $this->assertSame('minha-api-key-plana', $dto->api_key);
        $this->assertSame($user->created_at->format('Y-m-d H:i:s'), $dto->criado_em);
    }

    public function test_criado_em_usa_formato_y_m_d_h_i_s(): void
    {
        $user = User::factory()->create();

        $dto = RegisteredUserDTO::fromUser($user, 'chave');

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $dto->criado_em,
        );
    }

    public function test_expoe_chaves_esperadas_no_toarray(): void
    {
        $user = User::factory()->create();

        $output = RegisteredUserDTO::fromUser($user, 'chave')->toArray();

        $this->assertSame(
            ['ref', 'name', 'email', 'is_admin', 'api_key', 'criado_em'],
            array_keys($output),
        );
    }
}
