<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        $fileNumber = fake()->numberBetween(1, 999);

        return [
            'product_id' => Product::factory(),
            'path' => 'products/product-' . $fileNumber . '.jpg',
            'original_name' => 'product-' . $fileNumber . '.jpg',
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }
}
