<?php

namespace Database\Seeders;

use App\Enums\OrderStatusEnum;
use App\Models\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $descriptions = OrderStatusEnum::getDescriptionMap();

        foreach ($descriptions as $key => $description) {
            if (!OrderStatus::find($key)) {
                OrderStatus::create([
                    'id' => $key,
                    'description' => $description,
                ]);
            }
        }
    }
}
