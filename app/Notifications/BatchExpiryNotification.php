<?php

namespace App\Notifications;

use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BatchExpiryNotification extends Notification
{
    use Queueable;

    private $batch;

    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'batch_id' => $this->batch->id,
            'medicine' => optional($this->batch->purchase)->name,
            'batch_number' => $this->batch->batch_number,
            'expiry_date' => $this->batch->expiry_date->format('Y-m-d'),
            'quantity' => $this->batch->quantity_available,
            'message' => $this->batch->expiry_date->lt(Carbon::today())
                ? 'Expired batch still has stock.'
                : 'Batch is expiring within 30 days.',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
