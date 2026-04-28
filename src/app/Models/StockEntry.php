<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'entry_date',
        'units',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'unit_cost' => 'decimal:2',
        'units' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
