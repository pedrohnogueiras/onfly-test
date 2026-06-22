<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class PingTest extends TestCase
{
    use RefreshDatabase;

    public function test_retorna_pong_com_x_request_id(): void
    {
        $this->getJson('/ping', $this->requestIdHeader())
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Olá!!! =D']);
    }

    public function test_400_sem_x_request_id(): void
    {
        $this->getJson('/ping')
            ->assertStatus(400);
    }
}
