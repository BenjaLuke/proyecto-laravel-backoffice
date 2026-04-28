<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'return_date',
        'returned_units',
        'restocked_units',
        'defective_units',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'returned_units' => 'integer',
        'restocked_units' => 'integer',
        'defective_units' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
