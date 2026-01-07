<?php

namespace App\Http\Middleware;

use App\Filament\Pages\RoleSwitcher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserUnit
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
            'filament.admin.pages.update-user-unit',
            'filament.admin.auth.logout',
            // Tambahkan route yang memang tidak perlu ada user unit
        ];

        // $currentRouteName = $request->route()?->getName();
        // dd($currentRouteName);

        // if (!session()->has('active_role') && !in_array($request->route()->getName(), $exceptRoutes)) {
        //     return redirect()->route('filament.admin.pages.role-switcher');
        // }

        // if (!session()->has('active_role') && !in_array($request->route()->getName(), $exceptRoutes)) {
        //     return redirect()->route('filament.admin.pages.role-switcher');
        // }

        // perikan user jika sudah login dan mengecek apakah atribut kode_unit nya masih kosong
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->hasAnyRole('mhs')) {
                if (($user->kode_unit == null) && !in_array($request->route()->getName(), $exceptRoutes)) {
                    return redirect()->route('filament.admin.pages.update-user-unit');
                }
            }
        }

        return $next($request);
    }
}
