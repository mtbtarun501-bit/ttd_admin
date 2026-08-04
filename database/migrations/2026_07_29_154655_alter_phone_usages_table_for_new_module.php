<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First drop the old foreign keys if table exists
        if (Schema::hasTable('phone_usages')) {
            Schema::table('phone_usages', function (Blueprint $table) {
                // Ignore errors if keys don't exist
                try { $table->dropForeign(['booking_id']); } catch (\Exception $e) {}
                try { $table->dropForeign(['booking_type_id']); } catch (\Exception $e) {}
            });
            Schema::dropIfExists('phone_usages');
        }

        Schema::create('phone_usages', function (Blueprint $table) {
            $table->id();
            $table->string('member_name');
            $table->string('mobile_number')->unique();
            $table->string('status')->default('Active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_usages');
    }
};
