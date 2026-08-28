<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->is_superadmin || $user->hasRole('admin') || $this->ownsOrder($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order) && $order->canBeCancelled();
    }

    public function confirmPayment(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order) && $order->canBeConfirmed();
    }

    public function initiatePayment(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order) && $order->canBeConfirmed() && $order->balance_due > 0;
    }

    public function createChangeRequest(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order) && ! $order->isTerminal();
    }

    protected function ownsOrder(User $user, Order $order): bool
    {
        return $user->customer && $order->request->customer_id === $user->customer->id;
    }
}
