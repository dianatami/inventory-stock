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
    Schema::create('stock_mutations', function (Blueprint $table) {
        $table->id();

        $table->foreignId('product_id')
            ->constrained('products')
            ->restrictOnDelete();

        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->unsignedInteger('quantity');
        $table->string('from_location');
        $table->string('to_location');
        $table->string('status')->default('completed');
        $table->dateTime('mutation_date');
        $table->text('notes')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
