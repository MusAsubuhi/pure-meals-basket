<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function view(User $user, Quotation $quotation): bool
    {
        return $user->is_superadmin || $user->hasRole('admin') || $this->ownsRequest($user, $quotation);
    }

    public function accept(User $user, Quotation $quotation): bool
    {
        return $this->ownsRequest($user, $quotation) && $quotation->canBeAccepted();
    }

    public function decline(User $user, Quotation $quotation): bool
    {
        return $this->ownsRequest($user, $quotation) && $quotation->canBeDeclined();
    }

    public function requestChanges(User $user, Quotation $quotation): bool
    {
        return $this->ownsRequest($user, $quotation) && $quotation->canBeReplaced();
    }

    public function create(User $user): bool
    {
        return $user->is_superadmin || $user->hasRole('admin');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return ($user->is_superadmin || $user->hasRole('admin')) && $quotation->canBeEdited();
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return ($user->is_superadmin || $user->hasRole('admin')) && $quotation->canBeSent();
    }

    public function withdraw(User $user, Quotation $quotation): bool
    {
        return ($user->is_superadmin || $user->hasRole('admin')) && $quotation->canBeWithdrawn();
    }

    protected function ownsRequest(User $user, Quotation $quotation): bool
    {
        return $user->customer && $quotation->request->customer_id === $user->customer->id;
    }
}
