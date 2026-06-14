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
        Schema::table('products', function (Blueprint $blueprint) {
            // This drops the strict NOT NULL constraint on your database table
            $blueprint->decimal('wholesale_price', 10, 2)->nullable()->change();
            $blueprint->integer('wholesale_qty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $blueprint) {
            // Fallback recovery constraint adjustment if rolled back
            $blueprint->decimal('wholesale_price', 10, 2)->nullable(false)->change();
            $blueprint->integer('wholesale_qty')->nullable(false)->change();
        });
    }
};