<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\User;
use App\Notifications\BatchExpiryNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyExpiringBatches extends Command
{
    protected $signature = 'batches:notify-expiry';

    protected $description = 'Notify pharmacy staff about expired and near-expiry batches';

    public function handle(): int
    {
        $batches = Batch::with('purchase')
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays(30))
            ->orderBy('pharmacy_id')
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            if (!$batch->pharmacy_id) {
                continue;
            }

            User::where('pharmacy_id', $batch->pharmacy_id)
                ->get()
                ->each(function (User $user) use ($batch) {
                    $user->notify(new BatchExpiryNotification($batch));
                });
        }

        $this->info($batches->count() . ' expiring or expired batches notified.');

        return self::SUCCESS;
    }
}
