<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update the products table to allow decimal stock levels
        Schema::table('products', function (Blueprint $table) {
            // 8 total digits, 2 after the decimal point (e.g., 999999.99)
            $table->decimal('stock_level', 8, 2)->change();
        });

        // 2. Update the sale_items table to allow decimal quantities sold
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 8, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert columns back to regular integers if rolled back
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_level')->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
    }
};