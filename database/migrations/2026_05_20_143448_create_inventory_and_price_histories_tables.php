<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Logs historical price shifts
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('old_cost_price', 10, 2);
            $table->decimal('new_cost_price', 10, 2);
            $table->decimal('old_selling_price', 10, 2);
            $table->decimal('new_selling_price', 10, 2);
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // Logs physical stock manual updates and audits
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('old_stock');
            $table->integer('new_stock');
            $table->integer('delta'); 
            $table->string('remarks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('product_price_histories');
    }
};