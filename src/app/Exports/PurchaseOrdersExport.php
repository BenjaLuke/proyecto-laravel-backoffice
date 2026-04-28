<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrdersExport implements FromCollection, WithHeadings
{
    public function __construct(
        private string $month,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
    ) {
    }

    public function collection(): Collection
    {
        [$startDate, $endDate] = $this->resolveDates();

        return PurchaseOrder::with('product')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->orderBy('order_date')
            ->orderBy('id')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'fecha' => $order->order_date?->format('Y-m-d'),
                    'producto' => $order->product?->name ?? '-',
                    'unidades' => $order->units,
                    'precio_unitario' => number_format((float) $order->unit_price, 2, '.', ''),
                    'total' => number_format((float) $order->total_price, 2, '.', ''),
                    'estado' => $this->statusLabel($order->status),
                    'notas' => $order->notes ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Producto',
            'Unidades',
            'Precio unitario',
            'Total',
            'Estado',
            'Notas',
        ];
    }

    private function resolveDates(): array
    {
        $hasRange = filled($this->dateFrom) || filled($this->dateTo);

        if ($hasRange) {
            $start = filled($this->dateFrom)
                ? Carbon::parse($this->dateFrom)->toDateString()
                : Carbon::parse($this->dateTo)->toDateString();

            $end = filled($this->dateTo)
                ? Carbon::parse($this->dateTo)->toDateString()
                : Carbon::parse($this->dateFrom)->toDateString();

            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }

            return [$start, $end];
        }

        $currentMonth = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();

        return [
            $currentMonth->copy()->startOfMonth()->toDateString(),
            $currentMonth->copy()->endOfMonth()->toDateString(),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'prepared' => 'Preparado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default => 'Sin estado',
        };
    }
}