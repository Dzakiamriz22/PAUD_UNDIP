<?php

namespace App\Http\Middleware;

use App\Filament\Pages\RoleSwitcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $exceptRoutes = [
            'filament.admin.pages.dashboard',
            'filament.admin.pages.role-switcher',
            'filament.admin.pages.my-profile',
            'filament.admin.auth.login',
            'filament.admin.auth.logout',
            // Tambahkan route yang memang tidak perlu role aktif
        ];

        // $currentRouteName = $request->route()?->getName();
        // dd($currentRouteName);

        if (!session()->has('active_role') && !in_array($request->route()->getName(), $exceptRoutes)) {
            return redirect()->route('filament.admin.pages.role-switcher');
        }

        return $next($request);
    }
}
