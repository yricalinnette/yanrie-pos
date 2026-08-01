<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id', 
        'parent_id', 
        'conversion_factor', 
        'name', 
        'sku', 
        'is_weighed', 
        'cost_price', 
        'selling_price', 
        'stock_level', 
        'low_stock_threshold',
        'supplier_id',
        'delivery_date',
        'wholesale_price',
        'wholesale_qty',
        'hide_from_alerts',
        'is_active'
    ];

    /**
     * Get the Wholesale Parent Sack belonging to this retail item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    /**
     * Get any child retail items packed out of this wholesale sack.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_id')->latest();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    
    public function batches()
    {
        return $this->hasMany(ProductBatch::class)->latest();
    }

    public function stockAdjustments()
    {
        return $this->hasMany(\App\Models\StockAdjustment::class, 'product_id');
    }
}