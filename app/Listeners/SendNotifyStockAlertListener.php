<?php

namespace App\Listeners;

use App\Events\MedicineOutStock;
use App\Models\User;
use App\Notifications\SendNotifyStockAlertNotification;

class SendNotifyStockAlertListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MedicineOutStock $event)
    {
        $pharmacyId = $event->data->pharmacy_id;
        if (!$pharmacyId) {
            return;
        }

        $users = User::where('pharmacy_id', $pharmacyId)->get();
        foreach ($users as $user) {
            $user->notify(new SendNotifyStockAlertNotification($event->data));
        }
    }
}
