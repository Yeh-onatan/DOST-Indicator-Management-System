<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Office;
use App\Models\User;
use App\Models\Indicator as Objective;
use App\Models\DOSTAgency;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class AdminDashboard extends Component
{
    public string $activeTab = 'overview';

    public function mount()
    {
        // Only SA and Admin can access
        if (!Auth::user()->isSA() && !Auth::user()->isAdministrator()) {
            abort(403, 'Unauthorized access');
        }
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $stats = [
            'total_offices' => Office::count(),
            'active_offices' => Office::where('is_active', true)->count(),
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'total_agencies' => $this->countAgencies(),
            'total_indicators' => Objective::count(),
            'pending_approvals' => Objective::where('status', 'submitted_to_ho')->count(),
        ];

        return view('livewire.admin.admin-dashboard', compact('stats'));
    }

    protected function countAgencies(): int
    {
        return DOSTAgency::count();
    }
}
