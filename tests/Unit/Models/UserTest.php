<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // hashApiKey
    // -------------------------------------------------------------------------

    public function test_hash_api_key_e_deterministico(): void
    {
        $key = 'chave-de-teste-fixa';
        $hash1 = User::hashApiKey($key);
        $hash2 = User::hashApiKey($key);

        $this->assertSame($hash1, $hash2);
        // HMAC-SHA256 produz 64 caracteres hexadecimais
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash1);
    }

    public function test_hash_api_key_usa_salt_da_config(): void
    {
        $key = 'chave-qualquer';
        $hash = User::hashApiKey($key);

        $expected = hash_hmac('sha256', $key, config('jwt.hash_salt'));

        $this->assertSame($expected, $hash);
    }

    // -------------------------------------------------------------------------
    // generateApiKey
    // -------------------------------------------------------------------------

    public function test_generate_api_key_armazena_hash_e_retorna_plain(): void
    {
        $user = User::factory()->create();
        $plainKey = $user->generateApiKey();

        $this->assertNotEmpty($plainKey);
        $this->assertSame(48, strlen($plainKey)); // Str::random(48)

        // Hash deve estar salvo no banco
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'api_key' => User::hashApiKey($plainKey),
        ]);
    }

    // -------------------------------------------------------------------------
    // findByApiKey
    // -------------------------------------------------------------------------

    public function test_find_by_api_key_retorna_usuario_correto(): void
    {
        $user = User::factory()->create();
        $plainKey = $user->generateApiKey();

        $found = User::findByApiKey($plainKey);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_find_by_api_key_retorna_null_para_chave_invalida(): void
    {
        $result = User::findByApiKey('chave-inexistente-no-banco');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Geração de ref no boot com prefixo
    // -------------------------------------------------------------------------

    public function test_ref_gerado_no_boot_com_prefixo_usr(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->ref);
        $this->assertStringStartsWith(User::PREFIX_EXTERNAL_REFERENCE . '-', $user->ref);
    }

    public function test_refs_de_usuarios_distintos_sao_unicos(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertNotSame($user1->ref, $user2->ref);
    }

    public function test_ref_usa_ordered_uuid(): void
    {
        $user = User::factory()->create();

        // Formato esperado: "usr-" + UUID ordenado (36 chars: 8-4-4-4-12)
        $this->assertMatchesRegularExpression(
            '/^usr-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $user->ref,
        );
    }
}
