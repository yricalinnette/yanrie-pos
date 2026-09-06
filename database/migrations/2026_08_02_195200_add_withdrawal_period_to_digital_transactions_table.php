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
            // Stores 'daily' or 'monthly'
            $table->string('withdrawal_period_type')->nullable()->after('reference_number'); 
            // Stores the target value (e.g. '2026-07' or '2026-07-15')
            $table->string('withdrawal_period_target')->nullable()->after('withdrawal_period_type'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_transactions', function (Blueprint $table) {
            $table->dropColumn(['withdrawal_period_type', 'withdrawal_period_target']);
        });
    }
};