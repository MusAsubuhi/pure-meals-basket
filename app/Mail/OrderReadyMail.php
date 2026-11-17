<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderReadyMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject('Your PMB order is ready — '.$this->order->reference)
            ->markdown('emails.order.ready', [
                'order' => $this->order,
                'request' => $this->order->request,
            ]);
    }
}