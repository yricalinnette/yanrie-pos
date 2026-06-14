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
            // Defaults to the current timestamp so existing logs remain unaffected
            $table->timestamp('logged_at')->useCurrent()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('digital_transactions', function (Blueprint $table) {
            $table->dropColumn('logged_at');
        });
    }
};
