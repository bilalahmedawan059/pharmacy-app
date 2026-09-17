<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffInviteNotification extends Notification
{
    use Queueable;

    private $pharmacy;
    private $temporaryPassword;

    public function __construct($pharmacy, $temporaryPassword)
    {
        $this->pharmacy = $pharmacy;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your pharmacy account invitation')
            ->greeting('Welcome to ' . $this->pharmacy->business_name)
            ->line('Your staff account is ready.')
            ->line('Email: ' . $notifiable->email)
            ->line('Temporary password: ' . $this->temporaryPassword)
            ->action('Open Pharmacy App', url('/login'))
            ->line('Please change your password after signing in.');
    }
}
