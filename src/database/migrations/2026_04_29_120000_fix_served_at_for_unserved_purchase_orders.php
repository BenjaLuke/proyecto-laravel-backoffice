<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reparacion para bases ya migradas: served_at solo debe tener valor
        // cuando el pedido esta servido. Pendientes y cancelados no han movido
        // stock como servido, asi que su fecha de servicio debe quedar vacia.
        DB::table('purchase_orders')
            ->where('status', '!=', 'servido')
            ->update([
                'served_at' => null,
            ]);

        // Si algun pedido servido antiguo no tenia fecha, usamos created_at como
        // aproximacion historica para no perder la referencia temporal.
        DB::table('purchase_orders')
            ->where('status', 'servido')
            ->whereNull('served_at')
            ->update([
                'served_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        // No se revierte: volver a poner fechas incorrectas en pedidos no
        // servidos empeoraria los datos.
    }
};
