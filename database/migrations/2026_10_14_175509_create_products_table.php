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
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');

        $table->foreignId('category_id')
            ->constrained('categories')
            ->restrictOnDelete();

        $table->foreignId('supplier_id')
            ->nullable()
            ->constrained('suppliers')
            ->nullOnDelete();

        $table->string('unit', 30);
        $table->unsignedInteger('stock')->default(0);
        $table->unsignedInteger('minimum_stock')->default(0);
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
