<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlet_receipt_items', function (Blueprint $table) {
            $table->unsignedInteger('damaged_quantity')->default(0)->after('received_quantity');
            $table->unsignedInteger('short_quantity')->default(0)->after('damaged_quantity');
        });

        Schema::table('outlet_return_items', function (Blueprint $table) {
            $table->string('condition')->default('unspecified')->after('requested_quantity');
            $table->text('notes')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('outlet_return_items', function (Blueprint $table) {
            $table->dropColumn(['condition', 'notes']);
        });

        Schema::table('outlet_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['damaged_quantity', 'short_quantity']);
        });
    }
};
