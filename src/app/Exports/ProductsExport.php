<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize
{
    public function query()
    {
        return Product::query()
            ->with(['categories', 'rates', 'images'])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Código',
            'Nombre',
            'Categorías',
            'Descripción',
            'Tarifas',
            'Precio actual',
            'Imágenes',
        ];
    }

    public function map($product): array
    {
        $today = now()->toDateString();

        $currentRate = $product->rates
            ->filter(function ($rate) use ($today) {
                $start = $rate->start_date?->format('Y-m-d');
                $end = $rate->end_date?->format('Y-m-d');

                return $start <= $today && ($end === null || $end >= $today);
            })
            ->sortByDesc('start_date')
            ->first();

        return [
            $product->id,
            $product->code,
            $product->name,
            $product->categories->pluck('name')->implode(', '),
            $product->description ?: '',
            $product->rates->count(),
            $currentRate?->price ?? '',
            $product->images->count(),
        ];
    }
}
