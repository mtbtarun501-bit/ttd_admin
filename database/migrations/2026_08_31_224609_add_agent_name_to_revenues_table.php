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
        Schema::table('revenues', function (Blueprint $table) {
            $table->string('agent_name')->nullable()->after('source');
        });

        // Backfill existing data
        $revenues = \Illuminate\Support\Facades\DB::table('revenues')->get();
        foreach ($revenues as $revenue) {
            $agentName = 'Unknown';
            if ($revenue->revenue_import_booking_id) {
                $importBooking = \Illuminate\Support\Facades\DB::table('revenue_import_bookings')->find($revenue->revenue_import_booking_id);
                if ($importBooking) {
                    $agentName = $importBooking->agent_name;
                }
            } else {
                $parts = explode(' - ', $revenue->source);
                if (count($parts) > 1) {
                    $agentName = trim($parts[0]);
                } else {
                    $agentName = trim($revenue->source);
                }
            }
            \Illuminate\Support\Facades\DB::table('revenues')->where('id', $revenue->id)->update(['agent_name' => $agentName]);
        }
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->dropColumn('agent_name');
        });
    }
};
