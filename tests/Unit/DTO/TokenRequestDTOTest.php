<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Auth\TokenRequestDTO;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class TokenRequestDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Input mapping
    // -------------------------------------------------------------------------

    public function test_maps_x_api_key_to_api_key_property(): void
    {
        $dto = TokenRequestDTO::from(['x_api_key' => 'my-secret-token']);

        $this->assertSame('my-secret-token', $dto->api_key);
    }

    public function test_api_key_property_is_string(): void
    {
        $dto = TokenRequestDTO::from(['x_api_key' => 'abc123']);

        $this->assertIsString($dto->api_key);
    }

    // -------------------------------------------------------------------------
    // toArray output — no MapOutputName, so property name is preserved
    // -------------------------------------------------------------------------

    public function test_toArray_exposes_api_key_key(): void
    {
        $dto = TokenRequestDTO::from(['x_api_key' => 'my-secret-token']);
        $output = $dto->toArray();

        $this->assertArrayHasKey('api_key', $output);
        $this->assertSame('my-secret-token', $output['api_key']);
    }

    public function test_toArray_does_not_expose_x_api_key_key(): void
    {
        $dto = TokenRequestDTO::from(['x_api_key' => 'my-secret-token']);
        $output = $dto->toArray();

        $this->assertArrayNotHasKey('x_api_key', $output);
    }

    // -------------------------------------------------------------------------
    // Different values
    // -------------------------------------------------------------------------

    public function test_preserves_exact_api_key_value(): void
    {
        $key = 'Bearer eyJhbGciOiJIUzI1NiJ9.test';

        $dto = TokenRequestDTO::from(['x_api_key' => $key]);

        $this->assertSame($key, $dto->api_key);
    }
}
