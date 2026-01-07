<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class RoleButton extends Component
{
    public $active_role;

    public function mount()
    {
        $this->active_role = session()->get('active_role');
    }

    #[On('role-switched')]
    public function refresh()
    {
        $this->active_role = session()->get('active_role');
    }

    public function render()
    {
        return view('livewire.role-button');
    }
}
