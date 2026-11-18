<?php

return [
    /*
    | Online M-Pesa STK push needs a real PaymentGateway binding (see
    | AppServiceProvider). Leave false while the ManualPaymentGateway is used.
    */
    'mpesa_stk_enabled' => (bool) env('PMB_MPESA_STK_ENABLED', false),

    'mpesa_instructions' => env('PMB_MPESA_INSTRUCTIONS', 'Pay via M-Pesa to PMB, quoting your order reference. PMB will confirm your payment.'),
];
