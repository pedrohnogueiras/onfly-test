<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatusEnum;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class OrderStatusEnumTest extends TestCase
{
    // -------------------------------------------------------------------------
    // fromValue — valid values
    // -------------------------------------------------------------------------

    public function test_fromValue_returns_registred_case(): void
    {
        $case = OrderStatusEnum::fromValue(1);

        $this->assertSame(OrderStatusEnum::Registred, $case);
    }

    public function test_fromValue_returns_approved_case(): void
    {
        $case = OrderStatusEnum::fromValue(2);

        $this->assertSame(OrderStatusEnum::Approved, $case);
    }

    public function test_fromValue_returns_cancelled_case(): void
    {
        $case = OrderStatusEnum::fromValue(3);

        $this->assertSame(OrderStatusEnum::Cancelled, $case);
    }

    // -------------------------------------------------------------------------
    // fromValue — invalid value
    // -------------------------------------------------------------------------

    public function test_fromValue_throws_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order status: 99');

        OrderStatusEnum::fromValue(99);
    }

    public function test_fromValue_throws_for_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        OrderStatusEnum::fromValue(0);
    }

    // -------------------------------------------------------------------------
    // description()
    // -------------------------------------------------------------------------

    public function test_registred_description(): void
    {
        $this->assertSame('Solicitado', OrderStatusEnum::Registred->description());
    }

    public function test_approved_description(): void
    {
        $this->assertSame('Aprovado', OrderStatusEnum::Approved->description());
    }

    public function test_cancelled_description(): void
    {
        $this->assertSame('Cancelado', OrderStatusEnum::Cancelled->description());
    }

    // -------------------------------------------------------------------------
    // getDescriptionMap()
    // -------------------------------------------------------------------------

    public function test_getDescriptionMap_contains_all_cases(): void
    {
        $map = OrderStatusEnum::getDescriptionMap();

        $this->assertIsArray($map);
        $this->assertArrayHasKey(OrderStatusEnum::Registred->value, $map);
        $this->assertArrayHasKey(OrderStatusEnum::Approved->value, $map);
        $this->assertArrayHasKey(OrderStatusEnum::Cancelled->value, $map);
    }

    public function test_getDescriptionMap_values_are_correct(): void
    {
        $map = OrderStatusEnum::getDescriptionMap();

        $this->assertSame('Solicitado', $map[1]);
        $this->assertSame('Aprovado', $map[2]);
        $this->assertSame('Cancelado', $map[3]);
    }

    // -------------------------------------------------------------------------
    // getCases()
    // -------------------------------------------------------------------------

    public function test_getCases_returns_array_with_all_cases(): void
    {
        $cases = OrderStatusEnum::getCases();

        $this->assertIsArray($cases);
        $this->assertCount(3, $cases);
    }

    public function test_getCases_formats_entries_as_value_dash_description(): void
    {
        $cases = OrderStatusEnum::getCases();

        $this->assertContains('1 - Solicitado', $cases);
        $this->assertContains('2 - Aprovado', $cases);
        $this->assertContains('3 - Cancelado', $cases);
    }
}
