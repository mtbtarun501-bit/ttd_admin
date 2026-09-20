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
            $table->foreignId('referred_devotee_id')->nullable()->after('head_devotee_id')->constrained('devotees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devotees', function (Blueprint $table) {
            $table->dropForeign(['referred_devotee_id']);
            $table->dropColumn('referred_devotee_id');
        });
    }
};
