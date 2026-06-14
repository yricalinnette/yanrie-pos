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
        Schema::table('sale_items', function (Blueprint $table) {
            // Changes quantity from INT to DECIMAL(8,2) to safely accept numbers like 1.5
            $table->decimal('quantity', 8, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Rollback fallback path back to standard integer if ever needed
            $table->integer('quantity')->change();
        });
    }
};