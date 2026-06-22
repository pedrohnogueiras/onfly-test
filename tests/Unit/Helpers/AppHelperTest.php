<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\AppHelper;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class AppHelperTest extends TestCase
{
    // UUID v4 pattern: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    // -------------------------------------------------------------------------
    // With prefix
    // -------------------------------------------------------------------------

    public function test_with_prefix_starts_with_prefix_dash(): void
    {
        $ref = AppHelper::createExternalReference('usr');

        $this->assertStringStartsWith('usr-', $ref);
    }

    public function test_with_prefix_format_is_prefix_dash_uuid(): void
    {
        $ref = AppHelper::createExternalReference('ord');
        $uuidPart = substr($ref, strlen('ord-'));

        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $uuidPart);
    }

    public function test_with_prefix_returns_string(): void
    {
        $ref = AppHelper::createExternalReference('usr');

        $this->assertIsString($ref);
    }

    public function test_with_different_prefixes(): void
    {
        foreach (['usr', 'ord', 'pmt'] as $prefix) {
            $ref = AppHelper::createExternalReference($prefix);

            $this->assertStringStartsWith("{$prefix}-", $ref, "Expected prefix '{$prefix}-'");
        }
    }

    public function test_with_prefix_uuid_segment_has_correct_length(): void
    {
        $ref = AppHelper::createExternalReference('usr');
        $uuidPart = substr($ref, strlen('usr-'));

        // A UUID string is always 36 characters: 8-4-4-4-12 + 4 dashes
        $this->assertSame(36, strlen($uuidPart));
    }

    // -------------------------------------------------------------------------
    // Without prefix (empty string default)
    // -------------------------------------------------------------------------

    public function test_without_prefix_returns_bare_uuid(): void
    {
        $ref = AppHelper::createExternalReference();

        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $ref);
    }

    public function test_without_prefix_does_not_start_with_dash(): void
    {
        $ref = AppHelper::createExternalReference();

        $this->assertStringStartsNotWith('-', $ref);
    }

    public function test_empty_string_prefix_behaves_same_as_no_prefix(): void
    {
        $ref = AppHelper::createExternalReference('');

        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $ref);
    }

    // -------------------------------------------------------------------------
    // Uniqueness
    // -------------------------------------------------------------------------

    public function test_generates_unique_references(): void
    {
        $refs = [];

        for ($i = 0; $i < 10; $i++) {
            $refs[] = AppHelper::createExternalReference('usr');
        }

        $this->assertSame(count($refs), count(array_unique($refs)));
    }

    // -------------------------------------------------------------------------
    // User model constant integration
    // -------------------------------------------------------------------------

    public function test_user_prefix_constant_produces_usr_prefixed_reference(): void
    {
        $prefix = \App\Models\User::PREFIX_EXTERNAL_REFERENCE;
        $ref = AppHelper::createExternalReference($prefix);

        $this->assertStringStartsWith('usr-', $ref);
    }
}
