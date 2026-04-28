<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PROD-###??'),
            'name' => fake()->unique()->words(rand(1, 4), true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}