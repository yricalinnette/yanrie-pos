<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add a clean, nullable plain text string column to products
        Schema::table('products', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('supplier_name');
        });
    }
};