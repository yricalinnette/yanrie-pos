<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductBatch;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use a transaction to ensure data integrity
        DB::transaction(function () {
            // 1. Target products that currently have no history records
            $products = Product::whereDoesntHave('batches')->get();

            foreach ($products as $product) {
                // 2. Sum up total sales from the sale_items table
                $totalSold = DB::table('sale_items')
                    ->where('product_id', $product->id)
                    ->sum('quantity');

                // 3. Calculate: Initial Stock = Current Stock + Total Quantity Sold
                $initialStock = (float)$product->stock_level + (float)$totalSold;

                // 4. Create the batch record if there was ever stock
                if ($initialStock > 0) {
                    ProductBatch::create([
                        'product_id'     => $product->id,
                        'supplier_id'    => $product->supplier_id,
                        'cost_price'     => $product->cost_price,
                        'selling_price'  => $product->selling_price,
                        'quantity_added' => $initialStock,
                        // Use original delivery date or creation date as the batch date
                        'delivery_date'  => $product->delivery_date ?? $product->created_at ?? now(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: This removes all batches if you rollback, 
        // though usually, you'd want to keep them once migrated.
        // ProductBatch::truncate();
    }
};