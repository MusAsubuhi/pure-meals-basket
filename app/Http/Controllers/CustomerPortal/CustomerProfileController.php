<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $customer = $user->customer;

        return view('customer.profile', compact('user', 'customer'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $customer = $user->customer;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update(['name' => $validated['name']]);

        $customer->update([
            'phone' => $validated['phone'] ?? null,
            'address_line1' => $validated['address_line1'] ?? null,
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
        ]);

        return back()->with('success', 'Profile updated.');
    }
}
