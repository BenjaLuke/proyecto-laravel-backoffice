<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductBackofficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_product_with_new_primary_image_logs_final_image_state(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'is_admin' => true,
            'permissions' => User::defaultPermissions(),
        ]);

        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'code' => 'PROD-001',
            'name' => 'Producto original',
            'description' => 'Descripcion original',
            'current_stock' => 5,
            'min_stock' => 1,
        ]);

        $product->categories()->attach($category);

        ProductRate::factory()->create([
            'product_id' => $product->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'price' => 10,
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/existing.jpg',
            'original_name' => 'existing.jpg',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'code' => 'PROD-001',
            'name' => 'Producto actualizado',
            'description' => 'Descripcion actualizada',
            'min_stock' => 2,
            'categories' => [$category->id],
            'rates' => [
                [
                    'start_date' => '2026-01-01',
                    'end_date' => null,
                    'price' => 12.50,
                ],
            ],
            'primary_image_source' => 'new:0',
            'images' => [
                UploadedFile::fake()->image('new-primary.png'),
            ],
        ]);

        $response->assertRedirect(route('products.index'));

        $newImage = $product->images()
            ->where('original_name', 'new-primary.png')
            ->firstOrFail();

        $this->assertTrue($newImage->is_primary);
        Storage::disk('public')->assertExists($newImage->path);

        $log = $product->activityLogs()->where('action', 'updated')->firstOrFail();
        $imagesAfter = collect($log->changes['images']['after']);

        $loggedNewImage = $imagesAfter->firstWhere('original_name', 'new-primary.png');

        $this->assertNotNull($loggedNewImage);
        $this->assertTrue($loggedNewImage['is_primary']);
    }
}
