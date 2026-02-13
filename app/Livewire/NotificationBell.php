<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;
    public bool $showDropdown = false;

    protected $listeners = ['notificationCreated' => 'updateUnreadCount'];

    public function mount()
    {
        $this->updateUnreadCount();
    }

    public function updateUnreadCount()
    {
        $user = Auth::user();
        if ($user) {
            $this->unreadCount = \App\Services\NotificationService::make()
                ->getUnreadCount($user);
        }
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
