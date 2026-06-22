<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\JwtService;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class JwtServiceTest extends TestCase
{
    private JwtService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new JwtService();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(string $ref = 'usr-test-ref-abcdef'): User
    {
        $user = new User();
        $user->ref = $ref;

        return $user;
    }

    // -------------------------------------------------------------------------
    // encode / decode round-trip
    // -------------------------------------------------------------------------

    public function test_encode_returns_non_empty_string(): void
    {
        $token = $this->service->encode($this->makeUser());

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_encode_returns_three_segment_jwt(): void
    {
        $token = $this->service->encode($this->makeUser());

        $this->assertSame(3, substr_count($token, '.') + 1 - substr_count($token, '.') + substr_count($token, '.'));
        // More explicit: a JWT has exactly two dots → three segments
        $this->assertSame(2, substr_count($token, '.'));
    }

    public function test_decode_returns_object(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $this->assertIsObject($decoded);
    }

    public function test_round_trip_preserves_sub_claim(): void
    {
        $ref = 'usr-round-trip-xyz';
        $token = $this->service->encode($this->makeUser($ref));

        $decoded = $this->service->decode($token);

        $this->assertSame($ref, $decoded->sub);
    }

    public function test_round_trip_with_different_ref_values(): void
    {
        foreach (['usr-aaa', 'usr-bbb', 'usr-ccc'] as $ref) {
            $token = $this->service->encode($this->makeUser($ref));
            $decoded = $this->service->decode($token);

            $this->assertSame($ref, $decoded->sub, "sub mismatch for ref={$ref}");
        }
    }

    // -------------------------------------------------------------------------
    // Payload claims
    // -------------------------------------------------------------------------

    public function test_payload_contains_iss_claim(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $this->assertObjectHasProperty('iss', $decoded);
        $this->assertNotEmpty($decoded->iss);
    }

    public function test_iss_matches_app_url_config(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $this->assertSame(config('app.url'), $decoded->iss);
    }

    public function test_payload_contains_iat_claim(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertIsInt((int) $decoded->iat);
    }

    public function test_payload_contains_exp_claim(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $this->assertObjectHasProperty('exp', $decoded);
        $this->assertIsInt((int) $decoded->exp);
    }

    public function test_payload_contains_sub_claim(): void
    {
        $token = $this->service->encode($this->makeUser('usr-sub-check'));
        $decoded = $this->service->decode($token);

        $this->assertObjectHasProperty('sub', $decoded);
        $this->assertSame('usr-sub-check', $decoded->sub);
    }

    // -------------------------------------------------------------------------
    // Expiration matches configured TTL
    // -------------------------------------------------------------------------

    public function test_exp_minus_iat_equals_configured_ttl(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $ttl = (int) config('jwt.ttl');
        $diff = (int) $decoded->exp - (int) $decoded->iat;

        $this->assertSame($ttl, $diff);
    }

    public function test_exp_is_in_the_future(): void
    {
        $token = $this->service->encode($this->makeUser());
        $decoded = $this->service->decode($token);

        $this->assertGreaterThan(time(), (int) $decoded->exp);
    }

    public function test_iat_is_approximately_now(): void
    {
        $before = time();
        $token = $this->service->encode($this->makeUser());
        $after = time();
        $decoded = $this->service->decode($token);

        $this->assertGreaterThanOrEqual($before, (int) $decoded->iat);
        $this->assertLessThanOrEqual($after, (int) $decoded->iat);
    }
}
