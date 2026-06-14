<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Exception;

class PosService
{
    /**
     * Executes a transaction, updates stock, and logs itemized details.
     * 
     * @param array $cartItems Format: [['id' => 1, 'qty' => 2], ...]
     * @throws Exception
     */
    // 1. Accept $discount as an optional third parameter
    public function checkout(array $cartItems, float $cashProvided, float $discount = 0.0, string $transactionDate): Sale
    {
        return DB::transaction(function () use ($cartItems, $cashProvided, $discount, $transactionDate) {
            $subtotalSum = 0.0;
            $itemsToCreate = [];

            foreach ($cartItems as $item) {
                $productId = $item['product_id'] ?? ($item['id'] ?? null);
                $product = Product::lockForUpdate()->findOrFail($productId);
                
                // FIX: Safe array structural fallbacks to avoid "Undefined array key" crashes
                $quantitySold = (float)($item['quantity'] ?? ($item['qty'] ?? 1));
                
                // FIX: Captures alternate frontend names for wholesale flags (is_wholesale or wholesale)
                $isWholesaleItem = !empty($item['is_wholesale']) || !empty($item['wholesale']);

                // 1. Assign exact designated unit transaction pricing structures
                if ($isWholesaleItem && (float)$product->wholesale_price > 0) {
                    $unitPrice = (float)$product->wholesale_price;
                    
                    // 2. Stock Deduction Rule: Calculate loose piece unit volume depletion
                    $multiplier = ($product->wholesale_qty > 0) ? (float)$product->wholesale_qty : 1.0;
                    $qtyToDeduct = $quantitySold * $multiplier;
                } else {
                    $unitPrice = (float)$product->selling_price;
                    $qtyToDeduct = $quantitySold;
                    $isWholesaleItem = false; // Force false fallback if no wholesale price exists
                }

                // 3. Prevent Stock Deficits
                if ($product->stock_level < $qtyToDeduct) {
                    throw new \Exception("Insufficient stock for {$product->name}. Needed: {$qtyToDeduct}, Available: {$product->stock_level}");
                }

                // 4. Mathematical Subtotal Processing
                $itemSubtotal = round($unitPrice * $quantitySold, 2);
                $subtotalSum += $itemSubtotal;

                // 5. Build Snapshot Data Array
                $itemsToCreate[] = [
                    'product_id'    => $product->id,
                    'quantity'      => $quantitySold,
                    'unit_price'    => $unitPrice, 
                    'subtotal'      => $itemSubtotal,
                    'is_wholesale'  => $isWholesaleItem,
                    'cost_price'    => (float)$product->cost_price, // Snapshot cost price automatically from your products table
                    'wholesale_qty' => $isWholesaleItem ? (($product->wholesale_qty > 0) ? (int)$product->wholesale_qty : 1) : 1, // Snapshot wholesale qty factor
                ];

                // Decrement using compiled quantity volume values
                $product->decrement('stock_level', $qtyToDeduct);
            }

            // 6. Finalize Invoice Values
            $totalAmountDue = max(0.0, round($subtotalSum - $discount, 2));

            if ($cashProvided < $totalAmountDue) {
                throw new \Exception("Cash provided is less than the total amount due.");
            }

            // 7. Save Base Transaction Record
            $sale = Sale::create([
                'total_amount'  => $totalAmountDue,
                'discount'      => $discount,
                'amount_paid'   => $cashProvided,
                'change_amount' => max(0.0, round($cashProvided - $totalAmountDue, 2)),
                'created_at'    => $transactionDate,
                'updated_at'    => $transactionDate,
            ]);

            // 8. Hydrate and Create Relational Line Entries
            foreach ($itemsToCreate as $lineItem) {
                $lineItem['sale_id'] = $sale->id;
                $lineItem['created_at'] = $transactionDate;
                $lineItem['updated_at'] = $transactionDate;
                SaleItem::create($lineItem);
            }

            return $sale;
        });
    }
}