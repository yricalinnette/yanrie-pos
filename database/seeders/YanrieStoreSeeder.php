<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class YanrieStoreSeeder extends Seeder
{
    /**
     * Run the database seeds to populate Yanrie Variety Store.
     */
    public function run(): void
    {
        // 1. Create Quick Core Categories
        $rice = Category::create(['name' => 'Rice']);
        $snacks = Category::create(['name' => 'Snacks & Chips']);
        $beverages = Category::create(['name' => 'Beverages & Drinks']);
        $toiletries = Category::create(['name' => 'Personal Care & Toiletries']);
        $cannedGoods = Category::create(['name' => 'Canned & Instant Food']);
        $cigarettes = Category::create(['name' => 'Cigarettes']);
        $condiments = Category::create(['name' => 'Condiments']);
        $coffee = Category::create(['name' => 'Coffee & Milk']);
        $others = Category::create(['name' => 'Others']);

        // // 2. Insert Standard Non-Weighed Retail Products (All from INTEGRA)
        // Product::create([
        //     'category_id' => $snacks->id,
        //     'name' => 'Piattos Cheese 85g',
        //     'sku' => '4800016644211',
        //     'is_weighed' => false,
        //     'cost_price' => 28.00,
        //     'selling_price' => 35.50,
        //     'stock_level' => 50,
        //     'low_stock_threshold' => 5,
        //     'supplier_name' => 'INTEGRA'
        // ]);

        // Product::create([
        //     'category_id' => $beverages->id,
        //     'name' => 'Coca-Cola Mismo 290ml',
        //     'sku' => '4800888135763',
        //     'is_weighed' => false,
        //     'cost_price' => 15.00,
        //     'selling_price' => 20.00,
        //     'stock_level' => 100,
        //     'low_stock_threshold' => 10,
        //     'supplier_name' => 'INTEGRA'
        // ]);

        // Product::create([
        //     'category_id' => $cannedGoods->id,
        //     'name' => 'Lucky Me! Instant Pancit Canton Extra Hot Chili',
        //     'sku' => '4807770273214',
        //     'is_weighed' => false,
        //     'cost_price' => 11.50,
        //     'selling_price' => 16.00,
        //     'stock_level' => 120,
        //     'low_stock_threshold' => 15,
        //     'supplier_name' => 'INTEGRA'
        // ]);

        // Product::create([
        //     'category_id' => $toiletries->id,
        //     'name' => 'Safeguard Pure White Bar Soap 130g',
        //     'sku' => '4902430424523',
        //     'is_weighed' => false,
        //     'cost_price' => 38.00,
        //     'selling_price' => 45.00,
        //     'stock_level' => 30,
        //     'low_stock_threshold' => 5,
        //     'supplier_name' => 'INTEGRA'
        // ]);
        
        // Product::create([
        //     'category_id' => $cannedGoods->id,
        //     'name' => 'Century Tuna Flakes in Oil 155g',
        //     'sku' => '4800151121011',
        //     'is_weighed' => false,
        //     'cost_price' => 32.00,
        //     'selling_price' => 39.00,
        //     'stock_level' => 40,
        //     'low_stock_threshold' => 8,
        //     'supplier_name' => 'INTEGRA'
        // ]);

        // // =========================================================================
        // // 3. RICE INVENTORY UNIT RELATIONSHIPS DATA
        // // =========================================================================
        
        // // --- BRAND A: AQUARIUS RICE (From Aquarius Ricemill) ---
        // // A1. Create the Wholesale Parent Sack
        // $aquariusSack = Product::create([
        //     'category_id' => $rice->id,
        //     'parent_id' => null,
        //     'conversion_factor' => null,
        //     'name' => 'Aquarius Rice 50kg (Sack)',
        //     'sku' => '0000001111118',
        //     'is_weighed' => false, 
        //     'cost_price' => 2100.00,
        //     'selling_price' => 2250.00,
        //     'stock_level' => 10, 
        //     'low_stock_threshold' => 2,
        //     'supplier_name' => 'Aquarius Ricemill'
        // ]);

        // // A2. Create the Retail Child Per-Kilo variant
        // Product::create([
        //     'category_id' => $rice->id,
        //     'parent_id' => $aquariusSack->id, 
        //     'conversion_factor' => 50.00, 
        //     'name' => 'Aquarius Rice (Retail/Kilo)',
        //     'sku' => '0000001111156', 
        //     'is_weighed' => true, 
        //     'cost_price' => 42.00, 
        //     'selling_price' => 50.00, 
        //     'stock_level' => 0.00, 
        //     'low_stock_threshold' => 10,
        //     'supplier_name' => 'Aquarius Ricemill'
        // ]);

        // // --- BRAND B: DON ROBERTO RICE (From Cocomart) ---
        // // B1. Wholesale Parent Sack
        // $donRobertoSack = Product::create([
        //     'category_id' => $rice->id,
        //     'parent_id' => null,
        //     'conversion_factor' => null,
        //     'name' => 'Don Roberto Rice 50kg (Sack)',
        //     'sku' => '0000002222219',
        //     'is_weighed' => false,
        //     'cost_price' => 2400.00,
        //     'selling_price' => 2600.00,
        //     'stock_level' => 5,
        //     'low_stock_threshold' => 2,
        //     'supplier_name' => 'Cocomart'
        // ]);

        // // B2. Retail Child Per-Kilo variant
        // Product::create([
        //     'category_id' => $rice->id,
        //     'parent_id' => $donRobertoSack->id,
        //     'conversion_factor' => 50.00,
        //     'name' => 'Don Roberto Rice (Retail/Kilo)',
        //     'sku' => '0000002222257',
        //     'is_weighed' => true,
        //     'cost_price' => 48.00,
        //     'selling_price' => 58.00,
        //     'stock_level' => 0.00,
        //     'low_stock_threshold' => 10,
        //     'supplier_name' => 'Cocomart'
        // ]);
    }
}