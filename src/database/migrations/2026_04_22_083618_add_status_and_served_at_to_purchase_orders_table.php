<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_orders', 'status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('status')->nullable()->after('total_price');
            });
        }

        if (!Schema::hasColumn('purchase_orders', 'served_at')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dateTime('served_at')->nullable()->after('status');
            });
        }

        DB::table('purchase_orders')
            ->whereNull('served_at')
            ->update([
                'served_at' => DB::raw('created_at'),
            ]);

        DB::statement("
            UPDATE purchase_orders
            SET status = CASE
                WHEN status IS NULL OR status = '' THEN 'servido'
                WHEN status = 'pending' THEN 'pendiente'
                WHEN status = 'served' THEN 'servido'
                WHEN status = 'cancelled' THEN 'cancelado'
                WHEN status = 'pendiente' THEN 'pendiente'
                WHEN status = 'servido' THEN 'servido'
                WHEN status = 'cancelado' THEN 'cancelado'
                ELSE 'servido'
            END
        ");

        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY status ENUM('pendiente', 'servido', 'cancelado')
            NOT NULL DEFAULT 'pendiente'
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'served_at')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('served_at');
            });
        }

        if (Schema::hasColumn('purchase_orders', 'status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
