<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['receipt-submitted:%', 'receipt-validated:%'] as $eventPattern) {
            DB::table('system_notifications')
                ->where('event_key', 'like', $eventPattern)
                ->update(['audience' => json_encode(['Admin Prolog', 'Kepala Logistik'])]);
        }
    }

    public function down(): void
    {
        foreach (['receipt-submitted:%', 'receipt-validated:%'] as $eventPattern) {
            DB::table('system_notifications')
                ->where('event_key', 'like', $eventPattern)
                ->update(['audience' => json_encode(['Admin Prolog'])]);
        }
    }
};
