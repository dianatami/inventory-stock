<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'Manager', 'description' => 'Akses seluruh fitur', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Admin Prolog', 'description' => 'Operasional gudang dan distribusi', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kepala Logistik', 'description' => 'Persetujuan retur dan logistik', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'User Outlet', 'description' => 'Pengelolaan stok dan pesanan outlet', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('outlet_id')
                ->nullable()
                ->after('is_active')
                ->constrained('outlets')
                ->nullOnDelete();
            $table->unique('outlet_id');
        });

        Schema::create('outlet_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['outlet_id', 'product_id']);
        });

        Schema::create('outlet_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->dateTime('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(
                ['source_type', 'source_id', 'product_id'],
                'outlet_tx_source_unique'
            );
            $table->index(['outlet_id', 'transaction_date']);
        });

        Schema::create('outlet_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('requested');
            $table->text('notes')->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
            $table->index(['outlet_id', 'status']);
        });

        Schema::create('outlet_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->unsignedInteger('dispatched_quantity')->nullable();
            $table->timestamps();
            $table->unique(['outlet_order_id', 'product_id']);
        });

        Schema::create('outlet_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('submitted');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('outlet_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_order_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('received_quantity');
            $table->unsignedInteger('validated_quantity')->nullable();
            $table->timestamps();
            $table->unique(
                ['outlet_receipt_id', 'outlet_order_item_id'],
                'receipt_order_item_unique'
            );
        });

        Schema::create('outlet_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('outlet_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('requested');
            $table->text('reason');
            $table->text('decision_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['outlet_id', 'status']);
        });

        Schema::create('outlet_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('verified_quantity')->nullable();
            $table->timestamps();
            $table->unique(['outlet_return_id', 'product_id']);
        });

        Schema::create('outlet_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('stocktake_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['outlet_id', 'stocktake_date']);
        });

        Schema::create('outlet_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('opening_stock')->default(0);
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('closing_stock');
            $table->bigInteger('used_quantity');
            $table->bigInteger('discrepancy');
            $table->timestamps();
            $table->unique(['outlet_stocktake_id', 'product_id']);
        });

        Schema::create('low_stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_stock');
            $table->unsignedInteger('minimum_stock');
            $table->timestamp('notified_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('low_stock_notifications');
        Schema::dropIfExists('outlet_stocktake_items');
        Schema::dropIfExists('outlet_stocktakes');
        Schema::dropIfExists('outlet_return_items');
        Schema::dropIfExists('outlet_returns');
        Schema::dropIfExists('outlet_receipt_items');
        Schema::dropIfExists('outlet_receipts');
        Schema::dropIfExists('outlet_order_items');
        Schema::dropIfExists('outlet_orders');
        Schema::dropIfExists('outlet_stock_transactions');
        Schema::dropIfExists('outlet_stocks');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['outlet_id']);
            $table->dropConstrainedForeignId('outlet_id');
        });
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('roles');
    }
};
