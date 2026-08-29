<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $user = DB::transaction(function () use ($googleUser) {
            // 1. Existing Google user
            $user = User::where('google_id', $googleUser->getId())->first();

            if (! $user) {
                // 2. Existing email account — link Google, log in
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);

                    return $user;
                }
            }

            // 3. Brand-new Google signup — minimal profile, complete later
            if (! $user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: ($googleUser->user['given_name'] ?? 'Google User'),
                    'email' => $googleUser->getEmail(),
                    'password' => null,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'is_superadmin' => false,
                    'email_verified_at' => now(), // Google emails are pre-verified
                ]);

                $user->assignRole('customer');

                $customer = $user->customers()->create([
                    'status' => 'active',
                    // Contact/address intentionally empty — completed on profile page
                ]);

                $customer->account()->create([
                    'total_credit' => 0,
                    'total_debit' => 0,
                    'balance' => 0,
                ]);
            }
            // 4. Returning Google user with profile — refresh avatar
            elseif ($googleUser->getAvatar() && $user->avatar !== $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }

            return $user;
        });

        Auth::login($user);
        session()->regenerate();

        // New Google signups go straight to their profile to complete it
        if ($user->wasRecentlyCreated || is_null($user->customer?->phone) || is_null($user->customer?->address_line1)) {
            return redirect()
                ->route('customer.profile')
                ->with('profile_incomplete', 'Welcome! Please complete your profile so we can deliver your meals.');
        }

        return redirect()->route('customer.dashboard');
    }
}
