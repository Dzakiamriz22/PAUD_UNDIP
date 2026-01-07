<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class RoleInfo extends Component
{
    public $roles;

    public function mount()
    {
        $user = Auth::user();
        $this->roles = $user->roles->pluck('name')->toArray();
    }

    public function render()
    {
        return view('livewire.role-info');
    }
}
