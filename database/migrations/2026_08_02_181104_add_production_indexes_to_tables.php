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
        Schema::table('devotees', function (Blueprint $table) {
            $table->index('aadhaar');
            $table->index('phone');
            $table->index('name');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('booking_no');
            $table->index('booking_date');
            $table->index('status');
        });

        Schema::table('phone_usages', function (Blueprint $table) {
            $table->index('mobile_number');
            $table->index('status');
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->index('investor_name');
            $table->index('investment_date');
        });

        Schema::table('revenues', function (Blueprint $table) {
            $table->index('revenue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devotees', function (Blueprint $table) {
            $table->dropIndex(['aadhaar']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['name']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['booking_no']);
            $table->dropIndex(['booking_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('phone_usages', function (Blueprint $table) {
            $table->dropIndex(['mobile_number']);
            $table->dropIndex(['status']);
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropIndex(['investor_name']);
            $table->dropIndex(['investment_date']);
        });

        Schema::table('revenues', function (Blueprint $table) {
            $table->dropIndex(['revenue_date']);
        });
    }
};
