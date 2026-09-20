<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_holdings', function (Blueprint $table) {
            $table->id();
            $table->string('asset_type')->default('crypto'); // crypto | stock
            $table->string('symbol', 50);                     // e.g. BTC, RELIANCE.NS
            $table->string('name', 255);
            $table->decimal('quantity', 20, 8);
            $table->decimal('buy_price', 15, 2);
            $table->date('buy_date');
            $table->string('platform', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_type', 'symbol']);
            $table->index('buy_date');
        });

        // Migrate existing Indian Stock holdings into the unified portfolio table.
        if (Schema::hasTable('indian_stocks')) {
            DB::table('portfolio_holdings')->insertUsing(
                [
                    'asset_type', 'symbol', 'name', 'quantity', 'buy_price',
                    'buy_date', 'created_at', 'updated_at',
                ],
                DB::table('indian_stocks')
                    ->select(
                        DB::raw("'stock' as asset_type"),
                        'stock_symbol as symbol',
                        'stock_name as name',
                        'quantity',
                        'buy_price',
                        'buy_date',
                        DB::raw('NOW() as created_at'),
                        DB::raw('NOW() as updated_at')
                    )
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_holdings');
    }
};