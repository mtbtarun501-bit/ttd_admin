<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_usage_booking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_usage_id')->constrained('phone_usages')->cascadeOnDelete();
            $table->foreignId('seva_type_id')->constrained('seva_types')->cascadeOnDelete();
            $table->date('booking_date');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_usage_booking_histories');
    }
};
