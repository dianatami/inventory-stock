<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $audiences = [
            'order-request:%' => ['Admin Prolog'],
            'order-review:%' => ['Kepala Logistik'],
            'order-dispatch:%' => ['Admin Prolog'],
            'receipt-submitted:%' => ['Admin Prolog'],
            'receipt-validated:%' => ['Admin Prolog'],
        ];

        foreach ($audiences as $eventPattern => $audience) {
            DB::table('system_notifications')
                ->where('event_key', 'like', $eventPattern)
                ->update(['audience' => json_encode($audience)]);
        }
    }

    public function down(): void
    {
        foreach ([
            'order-request:%',
            'order-review:%',
            'order-dispatch:%',
            'receipt-submitted:%',
            'receipt-validated:%',
        ] as $eventPattern) {
            DB::table('system_notifications')
                ->where('event_key', 'like', $eventPattern)
                ->update(['audience' => json_encode(['Manager', 'Admin Prolog'])]);
        }
    }
};
