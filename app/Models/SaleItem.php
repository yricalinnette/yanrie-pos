<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'quantity', 'unit_price', 'subtotal', 'created_at', 'updated_at','is_wholesale', 
    'cost_price',    
    'wholesale_qty'];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'subtotal' => 'float',
    ];

    public function sale() {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
