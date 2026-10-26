<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlet_orders', function (Blueprint $table) {
            $table->foreignId('dispatched_by')
                ->nullable()
                ->after('dispatched_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('outlet_returns', function (Blueprint $table) {
            $table->foreignId('dispatched_by')
                ->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable()->after('dispatched_by');
        });

        Schema::table('outlet_return_items', function (Blueprint $table) {
            $table->unsignedInteger('shipped_quantity')->nullable()->after('requested_quantity');
        });

        Schema::table('low_stock_notifications', function (Blueprint $table) {
            $table->index(['product_id', 'resolved_at']);
        });

        Schema::table('low_stock_notifications', function (Blueprint $table) {
            $table->dropUnique('low_stock_notifications_product_id_unique');
            $table->string('severity', 20)->default('low')->after('minimum_stock');
            $table->timestamp('read_at')->nullable()->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('low_stock_notifications', function (Blueprint $table) {
            $table->unique('product_id');
        });

        Schema::table('low_stock_notifications', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'resolved_at']);
            $table->dropColumn(['severity', 'read_at']);
        });
        Schema::table('outlet_return_items', function (Blueprint $table) {
            $table->dropColumn('shipped_quantity');
        });

        Schema::table('outlet_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dispatched_by');
            $table->dropColumn('dispatched_at');
        });

        Schema::table('outlet_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dispatched_by');
        });
    }
};
