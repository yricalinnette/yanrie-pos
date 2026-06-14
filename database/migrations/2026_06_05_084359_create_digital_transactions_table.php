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
        Schema::create('digital_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('service_type'); // 'gcash_cash_in', 'gcash_cash_out', 'eload'
            $table->decimal('amount', 12, 2); 
            $table->decimal('service_fee', 10, 2); 
            
            // Ledger Balances for tracking physical vs. digital pools
            $table->decimal('cash_on_hand_change', 12, 2)->default(0.00);
            $table->decimal('gcash_balance_change', 12, 2)->default(0.00);
            
            $table->string('reference_number')->nullable(); 
            $table->string('customer_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_transactions');
    }
};
