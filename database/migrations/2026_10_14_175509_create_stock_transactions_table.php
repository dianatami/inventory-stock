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
    Schema::create('stock_transactions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('product_id')
            ->constrained('products')
            ->restrictOnDelete();

        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->enum('type', [
            'in',
            'out',
            'adjustment',
        ]);

        $table->unsignedInteger('quantity');
        $table->unsignedInteger('stock_before');
        $table->unsignedInteger('stock_after');

        $table->string('reference_number')->nullable();
        $table->dateTime('transaction_date');
        $table->text('notes')->nullable();

        $table->timestamps();

        $table->index(['type', 'transaction_date']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
