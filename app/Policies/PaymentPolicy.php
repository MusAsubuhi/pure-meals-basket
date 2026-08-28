<?php

namespace App\Policies;

use App\Enums\Payment\PaymentMethod;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->is_superadmin || $user->hasRole('admin') || $this->ownsPayment($user, $payment);
    }

    public function initiate(User $user, Payment $payment): bool
    {
        return $this->ownsPayment($user, $payment) && $payment->isPending();
    }

    public function confirmCash(User $user, Payment $payment): bool
    {
        return ($user->is_superadmin || $user->hasRole('admin')) && $payment->method === PaymentMethod::CASH && $payment->isPending();
    }

    protected function ownsPayment(User $user, Payment $payment): bool
    {
        return $user->customer && $payment->order->request->customer_id === $user->customer->id;
    }
}
