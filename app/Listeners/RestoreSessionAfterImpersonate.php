<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RestoreSessionAfterImpersonate
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
        if (session()->has('backup_active_role')) {
            session()->put('active_role', session('backup_active_role'));
            session()->put('active_role_id', session('backup_active_role_id'));

            session()->forget('backup_active_role');
            session()->forget('backup_active_role_id');
        }
    }
}
