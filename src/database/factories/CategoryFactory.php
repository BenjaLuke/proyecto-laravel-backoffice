<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CAT-###??'),
            'name' => fake()->unique()->words(rand(1, 3), true),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
        ];
    }
}
