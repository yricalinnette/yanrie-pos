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
        // STEP 1: Create Categories first so products can safely link to it!
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // STEP 2: Create Products and reference the categories table safely
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('sku')->unique();
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->decimal('selling_price', 12, 2)->default(0.00);
            $table->integer('stock_level')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();
        });

        // STEP 3: Create Sales
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('change_amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};