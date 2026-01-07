<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ClearSessionBeforeImpersonate
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if (session()->has('active_role')) {
            session()->put('backup_active_role', session('active_role'));
            session()->put('backup_active_role_id', session('active_role_id'));
            session()->forget('active_role');
            session()->forget('active_role_id');
        }
    }
}
