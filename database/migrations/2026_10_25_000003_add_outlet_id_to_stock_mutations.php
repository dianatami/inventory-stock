<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->foreignId('outlet_id')
                ->nullable()
                ->after('user_id')
                ->constrained('outlets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
        });
    }
};
