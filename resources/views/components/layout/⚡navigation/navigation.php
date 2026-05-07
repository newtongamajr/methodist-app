<?php

use App\Livewire\Actions\Logout;
use Livewire\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
};