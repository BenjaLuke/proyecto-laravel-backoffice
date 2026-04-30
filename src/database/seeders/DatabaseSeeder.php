<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductRate;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PRODUCT_COUNT = 36;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $msxCoverFiles = $this->getMsxCoverFiles();
        $coverIndex = 0;

        $adminPermissions = array_replace(User::defaultPermissions(), [
            'categories_view' => true,
            'categories_manage' => true,
            'categories_delete' => true,

            'products_view' => true,
            'products_manage' => true,
            'products_delete' => true,

            'calendar_view' => true,
            'calendar_manage' => true,
            'calendar_delete' => true,

            'activity_view' => true,
            'users_manage' => true,
        ]);

        $testUserPermissions = array_replace(User::defaultPermissions(), [
            'categories_view' => true,
            'products_view' => true,
            'calendar_view' => true,
        ]);

        User::updateOrCreate(
            ['username' => 'testuser'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'is_admin' => false,
                'permissions' => $testUserPermissions,
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'is_admin' => true,
                'permissions' => $adminPermissions,
                'password' => 'admin1234',
                'email_verified_at' => now(),
            ]
        );

        User::factory()->count(10)->create([
            'is_admin' => false,
            'permissions' => User::defaultPermissions(),
        ]);

        User::factory()->count(3)->unverified()->create([
            'is_admin' => false,
            'permissions' => User::defaultPermissions(),
        ]);

        $parentCategories = Category::factory()->count(4)->create();

        $childCategories = collect();

        $parentCategories->each(function ($parent) use (&$childCategories) {
            $children = Category::factory()->count(rand(1, 3))->create([
                'parent_id' => $parent->id,
            ]);

            $childCategories = $childCategories->merge($children);
        });

        $categories = $parentCategories->merge($childCategories);

        Product::factory()->count(self::PRODUCT_COUNT)->create()->each(function ($product) use ($categories, $msxCoverFiles, &$coverIndex) {
            $product->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray()
            );

            ProductRate::factory()->count(rand(1, 3))->create([
                'product_id' => $product->id,
            ]);

            if ($msxCoverFiles->isNotEmpty()) {
                $this->seedRealProductImages($product, $msxCoverFiles, $coverIndex);
            } else {
                ProductImage::factory()->count(rand(1, 4))->create([
                    'product_id' => $product->id,
                ]);
            }

            PurchaseOrder::factory()->count(rand(1, 5))->create([
                'product_id' => $product->id,
            ]);
        });
    }

    private function getMsxCoverFiles(): Collection
    {
        $sourcePath = storage_path('app/imports/msx-covers');

        if (! File::isDirectory($sourcePath)) {
            return collect();
        }

        return collect(File::files($sourcePath))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'bmp'], true))
            ->sortBy(fn ($file) => Str::lower($file->getFilename()))
            ->values();
    }

    private function seedRealProductImages(Product $product, Collection $msxCoverFiles, int &$coverIndex): void
    {
        $imageCount = min(rand(1, 4), $msxCoverFiles->count());
        $disk = Storage::disk('public');

        for ($sortOrder = 0; $sortOrder < $imageCount; $sortOrder++) {
            $cover = $msxCoverFiles[$coverIndex % $msxCoverFiles->count()];
            $coverIndex++;

            $extension = strtolower($cover->getExtension());
            $basename = pathinfo($cover->getFilename(), PATHINFO_FILENAME);
            $slug = Str::slug($basename);

            if ($slug === '') {
                $slug = 'cover-'.$product->id.'-'.$sortOrder;
            }

            $path = "products/{$product->id}/{$sortOrder}-{$slug}.{$extension}";

            File::ensureDirectoryExists(dirname($disk->path($path)));
            File::copy($cover->getRealPath(), $disk->path($path));

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'original_name' => $cover->getFilename(),
                'sort_order' => $sortOrder,
                'is_primary' => $sortOrder === 0,
            ]);
        }
    }
}