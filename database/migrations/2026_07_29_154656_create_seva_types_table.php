<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seva_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('cooldown_months');
            $table->integer('display_order')->default(0);
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seva_types');
    }
};
