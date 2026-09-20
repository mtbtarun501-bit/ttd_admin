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
        Schema::table('booking_types', function (Blueprint $table) {
            $table->decimal('in_partner_commission_rate', 10, 2)->default(0)->after('commission_rate');
            $table->decimal('out_partner_commission_rate', 10, 2)->default(0)->after('in_partner_commission_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_types', function (Blueprint $table) {
            $table->dropColumn(['in_partner_commission_rate', 'out_partner_commission_rate']);
        });
    }
};