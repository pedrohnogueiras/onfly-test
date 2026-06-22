<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\User\RegisterRequestDTO;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): UserService
    {
        return app(UserService::class);
    }

    private function dto(array $overrides = []): RegisterRequestDTO
    {
        return RegisterRequestDTO::from(array_merge([
            'nome' => 'Novo Usuario',
            'email' => 'novo@onfly.com.br',
            'password' => 'senha-segura-123',
        ], $overrides));
    }

    public function test_cria_usuario_com_is_admin_false_por_default(): void
    {
        $result = $this->service()->register($this->dto());

        $this->assertFalse($result->is_admin);
        $this->assertSame('novo@onfly.com.br', $result->email);

        $this->assertDatabaseHas('users', [
            'email' => 'novo@onfly.com.br',
            'is_admin' => false,
        ]);
    }

    public function test_honra_is_admin_true_do_payload(): void
    {
        $result = $this->service()->register($this->dto(['is_admin' => true]));

        $this->assertTrue($result->is_admin);

        $this->assertDatabaseHas('users', [
            'email' => 'novo@onfly.com.br',
            'is_admin' => true,
        ]);
    }

    public function test_armazena_senha_hasheada(): void
    {
        $this->service()->register($this->dto());

        $user = User::where('email', 'novo@onfly.com.br')->firstOrFail();

        $this->assertNotSame('senha-segura-123', $user->password);
        $this->assertTrue(Hash::check('senha-segura-123', $user->password));
    }

    public function test_retorna_api_key_plana_que_resolve_o_usuario(): void
    {
        $result = $this->service()->register($this->dto());

        $this->assertNotEmpty($result->api_key);

        $found = User::findByApiKey($result->api_key);

        $this->assertNotNull($found);
        $this->assertSame($result->ref, $found->ref);
    }

    public function test_gera_ref_com_prefixo_usr(): void
    {
        $result = $this->service()->register($this->dto());

        $this->assertStringStartsWith(User::PREFIX_EXTERNAL_REFERENCE . '-', $result->ref);
    }
}
