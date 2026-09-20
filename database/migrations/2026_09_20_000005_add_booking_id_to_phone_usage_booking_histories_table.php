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
        Schema::table('phone_usage_booking_histories', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('seva_type_id')->constrained('bookings')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_usage_booking_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
        });
    }
};