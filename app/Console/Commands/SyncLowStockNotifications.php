<?php

namespace App\Console\Commands;

use App\Services\LowStockNotificationService;
use Illuminate\Console\Command;

class SyncLowStockNotifications extends Command
{
    protected $signature = 'notifications:sync-stock';

    protected $description = 'Periksa dan sinkronkan notifikasi stok gudang';

    public function handle(LowStockNotificationService $notifications): int
    {
        $notifications->sync();
        $this->info('Notifikasi stok berhasil disinkronkan.');

        return self::SUCCESS;
    }
}
