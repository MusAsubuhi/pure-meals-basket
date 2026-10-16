<?php

namespace App\Notifications;

use App\Mail\QuotationSentMail;
use App\Models\Quotation;
use Illuminate\Bus\Queueable;

class QuotationSent extends PmbNotification
{
    use Queueable;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable)
    {
        return (new QuotationSentMail($this->quotation))->to($notifiable->email, $notifiable->name);
    }
}