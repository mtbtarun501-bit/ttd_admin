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
        Schema::create('indian_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('stock_symbol');
            $table->string('stock_name');
            $table->integer('quantity');
            $table->decimal('buy_price', 10, 2);
            $table->date('buy_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indian_stocks');
    }
};
