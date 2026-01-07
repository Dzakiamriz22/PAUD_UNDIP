<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CustomerOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('signin')->with('error', 'Please sign in to access this page.');
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasRole('customer')) {
            abort(403, 'Access denied. This page is only available for customers.');
        }

        // Check if email is verified
        if (empty($user->email_verified_at)) {
            return redirect()->route('signup-success');
        }

        return $next($request);
    }
}
