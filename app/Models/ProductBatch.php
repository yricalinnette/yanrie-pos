<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    // 🚀 Allow these columns to be mass-assigned
    protected $fillable = [
        'product_id',
        'supplier_id',
        'cost_price',
        'selling_price',
        'quantity_added',
        'delivery_date',
        'cost_per_box',  
        'items_per_box', 
        'num_boxes',
    ];

    /**
     * Get the product that owns this batch.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier for this specific batch delivery.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}