<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRate>
 */
class ProductRateFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-12 months', 'now');
        $hasEndDate = fake()->boolean(70);

        return [
            'product_id' => Product::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $hasEndDate
                ? fake()->dateTimeBetween($startDate, '+6 months')->format('Y-m-d')
                : null,
            'price' => fake()->randomFloat(2, 5, 500),
        ];
    }
}
