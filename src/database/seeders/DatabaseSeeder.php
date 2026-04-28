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

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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

        Product::factory()->count(12)->create()->each(function ($product) use ($categories) {
            $product->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray()
            );

            ProductRate::factory()->count(rand(1, 3))->create([
                'product_id' => $product->id,
            ]);

            ProductImage::factory()->count(rand(1, 4))->create([
                'product_id' => $product->id,
            ]);

            PurchaseOrder::factory()->count(rand(1, 5))->create([
                'product_id' => $product->id,
            ]);
        });
    }
}