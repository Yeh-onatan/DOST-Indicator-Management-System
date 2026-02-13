<?php

namespace App\Livewire\Super;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CreateAccount extends Component
{
    public $name = '';
    public $username = '';
    public $email = '';
    public $role = 'proponent';
    public $generated_password = '';
    public $show_password = false;

    protected $rules = [
        'name'     => 'required|string|max:255',
        'username' => 'required|string|alpha_dash|max:50|unique:users,username',
        'email'    => 'required|email:rfc,dns|unique:users,email',
        'role'     => 'required|in:proponent,administrator,super_admin,head_officer,ro,psto',
    ];

    public function generatePassword(): void
    {
        $this->generated_password = Str::password(12, symbols: true);
        $this->show_password = true;
    }

    public function save(): void
    {
        // SECURITY FIX: Verify user is super admin before creating accounts
        if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized: Only super administrators can create accounts.');
            return;
        }

        $this->validate();
        if (!$this->generated_password) {
            $this->generatePassword();
        }

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => Hash::make($this->generated_password),
            'role' => $this->role,
        ]);

        // Audit log (do not include the plain password)
        \App\Models\AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'User',
            'entity_id' => (string)$user->id,
            'changes' => [
                'diff' => [
                    'name'  => ['before' => null, 'after' => $this->name],
                    'username' => ['before' => null, 'after' => $this->username],
                    'email' => ['before' => null, 'after' => $this->email],
                    'role'  => ['before' => null, 'after' => $this->role],
                ],
            ],
        ]);

        session()->flash('success', 'Account created. Copy the password now; the user can change it later.');
        $this->show_password = true;
        // Keep name/username so admin can copy; clear email to avoid duplicates
        $this->email = '';
    }

    public function render()
    {
        return view('livewire.super.create-account')->layout('components.layouts.app');
    }
}
