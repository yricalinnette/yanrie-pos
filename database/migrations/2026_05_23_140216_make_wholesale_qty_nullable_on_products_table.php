<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // This drops the default constraint and allows the database engine to accept clean NULL records
            $table->integer('wholesale_qty')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('wholesale_qty')->nullable(false)->default(1)->change();
        });
    }
};
