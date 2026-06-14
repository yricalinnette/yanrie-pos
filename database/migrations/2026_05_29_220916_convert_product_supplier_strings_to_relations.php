<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure the suppliers table exists before executing logic
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        // 2. Extract unique supplier names currently used inside your products catalog
        $uniqueSupplierNames = DB::table('products')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->distinct()
            ->pluck('supplier_name');

        // 3. Insert those unique text strings as fresh records into the suppliers table
        foreach ($uniqueSupplierNames as $name) {
            $cleanName = trim($name);

            DB::table('suppliers')->updateOrInsert(
                ['name' => $cleanName],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // 4. Memory Backup: Grab a map of [product_id => supplier_name string] BEFORE dropping anything
        $backupMap = DB::table('products')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->pluck('supplier_name', 'id');

        // 5. Schema Overhaul: Drop the problematic text column and add the structural ID cleanly
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('supplier_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('category_id');
        });

        // 6. Match and Link: Loop through our memory backup map to insert the new IDs
        foreach ($backupMap as $productId => $oldSupplierName) {
            $matchedSupplier = DB::table('suppliers')
                ->where('name', trim($oldSupplierName))
                ->first();

            if ($matchedSupplier) {
                DB::table('products')
                    ->where('id', $productId)
                    ->update(['supplier_id' => $matchedSupplier->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'supplier_id')) {
                $table->dropColumn('supplier_id');
            }
            $table->string('supplier_name')->nullable()->after('category_id');
        });
    }
};