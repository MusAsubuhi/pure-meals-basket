<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderPreparingMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject('Your PMB order is being prepared — '.$this->order->reference)
            ->markdown('emails.order.preparing', [
                'order' => $this->order,
                'request' => $this->order->request,
            ]);
    }
}