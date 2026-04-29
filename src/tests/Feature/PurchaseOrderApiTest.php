<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductRate;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_without_status_stays_pending_without_served_date(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['calendar:write']);

        $product = Product::factory()->create([
            'current_stock' => 10,
            'min_stock' => 0,
        ]);

        ProductRate::factory()->create([
            'product_id' => $product->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'price' => 12.50,
        ]);

        $response = $this->postJson('/api/purchase-orders', [
            'order_date' => '2026-04-29',
            'product_id' => $product->id,
            'units' => 4,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.served_at', null);

        $this->assertSame(10, $product->refresh()->current_stock);

        $this->assertDatabaseHas('purchase_orders', [
            'product_id' => $product->id,
            'status' => 'pendiente',
            'served_at' => null,
        ]);

        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'source_type' => 'purchase_order',
        ]);
    }

    public function test_served_order_created_from_api_decreases_stock(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['calendar:write']);

        $product = Product::factory()->create([
            'current_stock' => 10,
            'min_stock' => 0,
        ]);

        ProductRate::factory()->create([
            'product_id' => $product->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'price' => 12.50,
        ]);

        $response = $this->postJson('/api/purchase-orders', [
            'order_date' => '2026-04-29',
            'product_id' => $product->id,
            'units' => 4,
            'status' => 'servido',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'servido')
            ->assertJsonPath('data.units', 4);

        $this->assertSame(6, $product->refresh()->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'order_served',
            'quantity' => -4,
            'stock_before' => 10,
            'stock_after' => 6,
            'source_type' => 'purchase_order',
        ]);
    }

    public function test_served_order_cannot_be_deleted_from_api(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['calendar:write']);

        $order = PurchaseOrder::factory()->create([
            'status' => 'servido',
            'served_at' => now(),
        ]);

        $response = $this->deleteJson("/api/purchase-orders/{$order->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('purchase_orders', ['id' => $order->id]);
    }
}
