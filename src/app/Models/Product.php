<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'current_stock',
        'min_stock',
    ];
    protected $casts = [
        'current_stock' => 'integer',
        'min_stock' => 'integer',
        'deleted_at' => 'datetime',
    ];
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function rates()
    {
        return $this->hasMany(ProductRate::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'entity_id')
            ->where('entity_type', 'product')
            ->latest();
    }

    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class)
            ->orderByDesc('entry_date')
            ->orderByDesc('id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)
            ->orderByDesc('movement_date')
            ->orderByDesc('id');
    }

    public function purchaseOrderReturns()
    {
        return $this->hasManyThrough(
            PurchaseOrderReturn::class,
            PurchaseOrder::class,
            'product_id',
            'purchase_order_id',
            'id',
            'id'
        );
    }
}
