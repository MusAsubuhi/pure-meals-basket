<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderCompletedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject('Your PMB order is complete — '.$this->order->reference)
            ->markdown('emails.order.completed', [
                'order' => $this->order,
                'request' => $this->order->request,
            ]);
    }
}