<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Auth\TokenRequestDTO;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AuthService
    {
        return app(AuthService::class);
    }

    public function test_authenticate_retorna_token_data_para_api_key_valida(): void
    {
        $user = User::factory()->create();
        $apiKey = $user->generateApiKey();

        $dto = TokenRequestDTO::from(['x_api_key' => $apiKey]);
        $result = $this->service()->authenticate($dto);

        $this->assertNotEmpty($result->access_token);
        $this->assertSame(AuthService::TOKEN_TYPE, $result->token_type);
    }

    public function test_authenticate_lanca_model_not_found_para_api_key_invalida(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dto = TokenRequestDTO::from(['x_api_key' => 'chave-invalida-qualquer']);
        $this->service()->authenticate($dto);
    }
}
