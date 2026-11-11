<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlet_orders', function (Blueprint $table) {
            $table->string('driver_name')->nullable();
        });

        Schema::create('logistics_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('unit', 50);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['requested_by', 'status']);
        });

        if (! DB::table('users')->where('username', 'mietime01')->exists()) {
            DB::table('users')
                ->where('username', 'omprof0001')
                ->update([
                    'username' => 'mietime01',
                    'name' => 'Manager Mie Time',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('users')
                ->where('username', 'mietime01')
                ->whereRaw('LOWER(name) = ?', ['manager omprof'])
                ->update(['name' => 'Manager Mie Time', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_requests');

        Schema::table('outlet_orders', function (Blueprint $table) {
            $table->dropColumn('driver_name');
        });

        if (! DB::table('users')->where('username', 'omprof0001')->exists()) {
            DB::table('users')
                ->where('username', 'mietime01')
                ->where('name', 'Manager Mie Time')
                ->update([
                    'username' => 'omprof0001',
                    'name' => 'Manager OMPROF',
                    'updated_at' => now(),
                ]);
        }
    }
};
