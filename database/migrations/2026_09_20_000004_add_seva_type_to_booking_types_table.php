<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_types', function (Blueprint $table) {
            $table->foreignId('seva_type_id')->nullable()->after('status')->constrained('seva_types')->nullOnDelete();
        });

        $mappings = [
            'Special Entry Darshan' => 'Special Entry Darshan (₹300)',
            'VIP Break Darshan' => 'Special Entry Darshan (₹300)',
            'Accommodation' => 'Accommodation',
            'Tirumala Accommodation (Rs. 999)' => 'Accommodation',
            'Tirumala Accommodation (Rs. 1999)' => 'Accommodation',
            'Virtual Seva' => 'Virtual Seva',
            'Virtual Seva and Arjitha Seva' => 'Arjitha Seva',
            'Angapradakshina' => 'Angapradakshanam',
            'Senior Citizen' => 'Senior Citizen Darshan',
            'Homam Tickets' => 'Srinivasa Divyanugraha Homam',
        ];

        foreach ($mappings as $bookingTypeName => $sevaTypeName) {
            DB::table('booking_types')
                ->where('name', $bookingTypeName)
                ->update([
                    'seva_type_id' => DB::table('seva_types')->where('name', $sevaTypeName)->value('id'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seva_type_id');
        });
    }
};