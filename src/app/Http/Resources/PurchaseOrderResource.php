<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'units' => $this->units,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'notes' => $this->notes,
            'product' => [
                'id' => $this->product?->id,
                'code' => $this->product?->code,
                'name' => $this->product?->name,
            ],
        ];
    }
}
