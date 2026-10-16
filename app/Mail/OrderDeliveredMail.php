<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderDeliveredMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject('Your PMB order has been delivered — '.$this->order->reference)
            ->view('emails.order.delivered', [
                'order' => $this->order,
                'request' => $this->order->request,
            ]);
    }
}