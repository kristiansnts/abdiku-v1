<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncIndonesiaHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Indonesian national holidays from official iCal API to master data and tenants';

    public function handle(\App\Domain\Leave\Services\IndonesiaHolidayService $service): void
    {
        $this->info('Starting Indonesian Holiday Sync...');
        
        $result = $service->syncMasterHolidays();

        if ($result['success']) {
            $this->success("Master holidays synced. Updated {$result['count']} items.");
            
            // Trigger distribution to tenants
            $this->info('Distributing updates to companies with auto-sync enabled...');
            $service->distributeToTenants();
            $this->info('Distribution complete.');
        } else {
            $this->error("Sync failed: {$result['error']}");
        }
    }
}
