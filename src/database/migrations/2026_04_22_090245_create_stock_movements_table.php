<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->enum('movement_type', [
                'entry',
                'order_served',
                'return_restock',
                'adjustment_plus',
                'adjustment_minus',
            ]);

            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');

            $table->dateTime('movement_date');

            $table->enum('source_type', [
                'stock_entry',
                'purchase_order',
                'purchase_order_return',
                'manual',
            ]);

            $table->unsignedBigInteger('source_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'movement_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
