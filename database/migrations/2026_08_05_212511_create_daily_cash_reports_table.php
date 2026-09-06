<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cash_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->decimal('beginning_cash', 12, 2)->default(0.00);
            $table->decimal('retail_rice_sales', 12, 2)->default(0.00);
            $table->decimal('wholesale_rice_sales', 12, 2)->default(0.00);
            $table->decimal('system_grocery_sales', 12, 2)->default(0.00);
            $table->decimal('manual_grocery_sales', 12, 2)->default(0.00);
            $table->decimal('total_expected_sales', 12, 2)->default(0.00);
            $table->decimal('payout_expenses', 12, 2)->default(0.00);
            $table->decimal('expected_cash_in_drawer', 12, 2)->default(0.00);
            $table->decimal('actual_cash_counted', 12, 2)->default(0.00);
            $table->decimal('cash_variance', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cash_reports');
    }
};