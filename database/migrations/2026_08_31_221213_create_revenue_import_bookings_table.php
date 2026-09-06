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
        Schema::create('revenue_import_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revenue_import_id')->constrained('revenue_imports')->cascadeOnDelete();
            $table->string('agent_name')->nullable();
            $table->string('booking_reference')->nullable();
            $table->string('service_name')->nullable();
            $table->decimal('ticket_cost', 15, 2)->default(0);
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->integer('member_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_import_bookings');
    }
};
