<?php

namespace App\Policies;

use App\Models\Fulfillment\Fulfillment;
use App\Models\User;

class FulfillmentPolicy
{
    public function view(User $user, Fulfillment $fulfillment): bool
    {
        return $user->is_superadmin || $user->hasRole('admin') || $this->ownsFulfillment($user, $fulfillment);
    }

    public function operate(User $user, Fulfillment $fulfillment): bool
    {
        return $user->is_superadmin || $user->hasRole('admin');
    }

    protected function ownsFulfillment(User $user, Fulfillment $fulfillment): bool
    {
        return $user->customer && $fulfillment->order->request->customer_id === $user->customer->id;
    }
}
