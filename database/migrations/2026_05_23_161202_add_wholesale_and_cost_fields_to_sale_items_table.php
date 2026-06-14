<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_wholesale')->default(false)->after('unit_price');
            $table->decimal('cost_price', 10, 2)->default(0.00)->after('is_wholesale');
            $table->integer('wholesale_qty')->default(1)->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_wholesale', 'cost_price', 'wholesale_qty']);
        });
    }
};