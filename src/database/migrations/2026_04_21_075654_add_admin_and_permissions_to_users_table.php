<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
            $table->json('permissions')->nullable()->after('is_admin');
        });

        DB::table('users')
            ->where('username', 'admin')
            ->update([
                'is_admin' => true,
                'permissions' => json_encode([
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
                ]),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'permissions']);
        });
    }
};