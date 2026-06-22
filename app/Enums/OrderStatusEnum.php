<?php

declare(strict_types=1);

namespace App\Enums;

use InvalidArgumentException;

enum OrderStatusEnum: int
{
    case Registred = 1;

    case Approved = 2;

    case Cancelled = 3;

    public static function getDescriptionMap(): array
    {
        return [
            self::Registred->value => 'Solicitado',
            self::Approved->value => 'Aprovado',
            self::Cancelled->value => 'Cancelado',
        ];
    }

    public static function fromValue(int $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Invalid order status: {$value}");
    }

    public static function getCases(): array
    {
        return array_map(
            fn (self $case) => "{$case->value} - {$case->description()}",
            self::cases(),
        );
    }

    public function description(): string
    {
        return self::getDescriptionMap()[$this->value];
    }

}
