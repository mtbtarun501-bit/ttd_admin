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
            $table->boolean('is_head_of_family')->default(false)->after('id');
            $table->foreignId('head_devotee_id')->nullable()->after('is_head_of_family')->constrained('devotees')->nullOnDelete();
            $table->foreignId('preferred_booking_type_id')->nullable()->after('head_devotee_id')->constrained('booking_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devotees', function (Blueprint $table) {
            $table->dropForeign(['head_devotee_id']);
            $table->dropForeign(['preferred_booking_type_id']);
            $table->dropColumn(['is_head_of_family', 'head_devotee_id', 'preferred_booking_type_id']);
        });
    }
};
