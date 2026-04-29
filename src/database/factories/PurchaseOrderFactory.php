<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        $units = fake()->numberBetween(1, 25);
        $unitPrice = fake()->randomFloat(2, 5, 500);

        return [
            'order_date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'product_id' => Product::factory(),
            'units' => $units,
            'unit_price' => $unitPrice,
            'total_price' => round($units * $unitPrice, 2),
            'status' => 'pendiente',
            'served_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
