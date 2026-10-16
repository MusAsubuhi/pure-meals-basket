<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderOutForDeliveryMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject('Your PMB order is on the way — '.$this->order->reference)
            ->view('emails.order.out-for-delivery', [
                'order' => $this->order,
                'request' => $this->order->request,
            ]);
    }
}