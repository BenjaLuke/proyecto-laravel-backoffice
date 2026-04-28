<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_date',
        'product_id',
        'units',
        'unit_price',
        'total_price',
        'status',
        'served_at',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'served_at' => 'datetime',
        'units' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'status' => 'string',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderReturns()
    {
        return $this->hasMany(PurchaseOrderReturn::class)
            ->orderByDesc('return_date')
            ->orderByDesc('id');
    }
}
