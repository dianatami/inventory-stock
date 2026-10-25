<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('suppliers')
            ->whereNull('whatsapp')
            ->whereNotNull('phone')
            ->update(['whatsapp' => DB::raw('phone')]);

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->foreignId('outlet_order_item_id')
                ->nullable()
                ->unique('stock_mutation_order_item_unique')
                ->constrained('outlet_order_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_order_item_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('phone', 30)->nullable();
        });

        DB::table('suppliers')
            ->whereNull('phone')
            ->whereNotNull('whatsapp')
            ->update(['phone' => DB::raw('whatsapp')]);
    }
};
