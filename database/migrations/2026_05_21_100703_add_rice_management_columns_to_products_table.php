<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 💡 ONLY add parent_id if it doesn't already exist from the previous crash
            if (!Schema::hasColumn('products', 'parent_id')) {
                $table->foreignId('parent_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('products')
                      ->onDelete('set null');
            }

            // 💡 ONLY add conversion_factor if it doesn't exist
            if (!Schema::hasColumn('products', 'conversion_factor')) {
                $table->decimal('conversion_factor', 8, 2)->nullable()->after('parent_id');
            }

            // 💡 ONLY add is_weighed if it doesn't exist
            if (!Schema::hasColumn('products', 'is_weighed')) {
                $table->boolean('is_weighed')->default(false)->after('sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key safely first if the column exists
            if (Schema::hasColumn('products', 'parent_id')) {
                $table->dropForeign(['parent_id']);
            }
            
            $table->dropColumn(array_filter([
                Schema::hasColumn('products', 'parent_id') ? 'parent_id' : null,
                Schema::hasColumn('products', 'conversion_factor') ? 'conversion_factor' : null,
                Schema::hasColumn('products', 'is_weighed') ? 'is_weighed' : null,
            ]));
        });
    }
};