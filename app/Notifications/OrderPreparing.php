<?php

namespace App\Notifications;

use App\Mail\OrderPreparingMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;

class OrderPreparing extends PmbNotification
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable)
    {
        return (new OrderPreparingMail($this->order))->to($notifiable->email, $notifiable->name);
    }
}