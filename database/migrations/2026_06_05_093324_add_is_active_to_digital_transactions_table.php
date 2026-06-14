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
        Schema::table('digital_transactions', function (Blueprint $table) {
            // Default to true so all existing logs remain visible
            $table->boolean('is_active')->default(true)->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('digital_transactions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
