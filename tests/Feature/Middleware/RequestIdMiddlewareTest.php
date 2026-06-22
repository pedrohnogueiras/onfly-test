<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class RequestIdMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_400_sem_header_x_request_id(): void
    {
        // Rota pública que ainda exige X-Request-Id (via middleware 'api')
        $this->getJson('/ping')
            ->assertStatus(400)
            ->assertJsonFragment(['message' => 'Header X-Request-Id é obrigatório.']);
    }

    public function test_passa_com_header_x_request_id_presente(): void
    {
        $this->getJson('/ping', $this->requestIdHeader())
            ->assertStatus(200);
    }
}
