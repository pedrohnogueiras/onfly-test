<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departure = $this->faker->dateTimeBetween('+1 days', '+30 days');
        $return = $this->faker->dateTimeBetween($departure, '+60 days');

        return [
            'user_id' => User::factory(),
            'applicant' => $this->faker->name(),
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => $return->format('Y-m-d'),
            'status_id' => OrderStatusEnum::Registred->value,
        ];
    }

    /**
     * State: define datas específicas de partida e retorno (formato Y-m-d).
     */
    public function withDates(string $departureDate, string $returnDate): static
    {
        return $this->state(fn (array $attributes) => [
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
        ]);
    }

    /**
     * State: pedido com status Aprovado.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => OrderStatusEnum::Approved->value,
        ]);
    }

    /**
     * State: pedido com status Cancelado.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => OrderStatusEnum::Cancelled->value,
        ]);
    }
}
