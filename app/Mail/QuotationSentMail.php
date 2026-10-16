<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Mail\Mailable;

class QuotationSentMail extends Mailable
{
    public function __construct(public Quotation $quotation) {}

    public function build()
    {
        return $this
            ->subject('Your PMB quotation is ready — '.$this->quotation->reference)
            ->view('emails.quotation.sent', [
                'quotation' => $this->quotation,
                'request' => $this->quotation->request,
            ]);
    }
}