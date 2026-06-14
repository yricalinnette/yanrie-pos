<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    protected $fillable = ['product_id', 'old_cost_price', 'new_cost_price', 'old_selling_price', 'new_selling_price', 'reason'];
}