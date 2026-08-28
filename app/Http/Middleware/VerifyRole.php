<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        // /admin is restricted to  admins (is_superadmin flag) or users with the admin role
        if ($role === 'admin') {
            if ($user->is_superadmin || $user->hasRole('admin')) {
                return $next($request);
            }

            return redirect('/customer');
        }

        // Allow access if user has the required role
        if ($user->hasRole($role)) {
            return $next($request);
        }

        if ($user->hasRole('admin')) {
            return redirect('/admin');
        }
        if ($user->hasRole('customer')) {
            return redirect('/customer');
        }

        Auth::logout();

        return redirect()->route('login')
            ->with('error', 'You do not have permission to access this area.');
    }
}
