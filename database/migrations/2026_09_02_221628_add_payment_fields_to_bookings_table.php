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
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('ticket_count')->default(1)->after('booking_date');
            $table->decimal('service_charge', 10, 2)->default(0)->after('ticket_count');
            $table->decimal('total_amount', 10, 2)->default(0)->after('service_charge');
            $table->string('booked_by_name')->nullable()->after('total_amount')->comment('Used to store Nikhil, Sis, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['ticket_count', 'service_charge', 'total_amount', 'booked_by_name']);
        });
    }
};
