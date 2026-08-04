<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_usage_service_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_usage_id')->constrained('phone_usages')->cascadeOnDelete();
            $table->foreignId('seva_type_id')->constrained('seva_types')->cascadeOnDelete();
            $table->date('last_booked_date')->nullable();
            $table->date('next_eligible_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_usage_service_statuses');
    }
};
