<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            // Nullable packaging properties to support both loose and bulk items
            $table->decimal('cost_per_box', 10, 2)->nullable()->after('supplier_id');
            $table->integer('items_per_box')->nullable()->after('cost_per_box');
            $table->integer('num_boxes')->nullable()->after('items_per_box');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn(['cost_per_box', 'items_per_box', 'num_boxes']);
        });
    }
};