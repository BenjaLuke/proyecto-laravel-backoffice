<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = now()->toDateString();

        $currentRate = $this->rates
            ->filter(function ($rate) use ($today) {
                $start = $rate->start_date?->format('Y-m-d');
                $end = $rate->end_date?->format('Y-m-d');

                return $start <= $today && ($end === null || $end >= $today);
            })
            ->sortByDesc('start_date')
            ->first();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'categories' => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
            'current_price' => $currentRate?->price,
            'rates_count' => $this->rates->count(),
            'images_count' => $this->images->count(),
        ];
    }
}