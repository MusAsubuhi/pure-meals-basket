<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderConfirmedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject('Your PMB order is confirmed — '.$this->order->reference)
            ->markdown('emails.order.confirmed', [
                'order' => $this->order,
                'request' => $this->order->request,
            ]);
    }
}