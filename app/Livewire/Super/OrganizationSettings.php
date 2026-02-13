<?php

namespace App\Livewire\Super;

use Livewire\Component;
use App\Models\AdminSetting;

class OrganizationSettings extends Component
{
    public $org_name;
    public $org_logo_path;
    public $theme_accent;
    public $timezone;
    public $locale;
    public $archive_years;
    public $regions_roles; // comma or JSON in UI
    public $compliance;    // JSON text

    protected $rules = [
        'org_name' => 'nullable|string',
        'org_logo_path' => 'nullable|string',
        'theme_accent' => 'nullable|string',
        'timezone' => 'nullable|string',
        'locale' => 'nullable|string',
        'archive_years' => 'nullable|integer|min:0',
    ];

    public function mount(): void
    {
        $s = AdminSetting::first();
        $this->org_name = $s?->org_name;
        $this->org_logo_path = $s?->org_logo_path;
        $this->theme_accent = $s?->theme_accent;
        $this->timezone = $s?->timezone;
        $this->locale = $s?->locale;
        $this->archive_years = $s?->archive_years;
        $this->regions_roles = $s?->regions_roles ? json_encode($s->regions_roles) : '';
        $this->compliance = $s?->compliance ? json_encode($s->compliance) : '';
    }

    public function save(): void
    {
        $this->validate();
        $s = AdminSetting::first() ?: new AdminSetting();
        $before = $s->toArray();
        $s->org_name = $this->org_name;
        $s->org_logo_path = $this->org_logo_path;
        $s->theme_accent = $this->theme_accent;
        $s->timezone = $this->timezone;
        $s->locale = $this->locale;
        $s->archive_years = $this->archive_years ?: null;
        $s->regions_roles = $this->decodeJsonField($this->regions_roles);
        $s->compliance = $this->decodeJsonField($this->compliance);
        $s->save();
        // Build a compact, field-by-field diff of changes. This shape is what the
        // Audit Logs UI expects so it can render "Before → After" cleanly.
        $diff = [];
        foreach (['org_name','org_logo_path','theme_accent','timezone','locale','archive_years','regions_roles','compliance'] as $k) {
            $beforeVal = $before[$k] ?? null;
            $afterVal = $s->$k;
            if ($beforeVal != $afterVal) {
                $diff[$k] = ['before' => $beforeVal, 'after' => $afterVal];
            }
        }
        // Persist the update event
        \App\Models\AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'AdminSetting',
            'entity_id' => (string)$s->id,
            'changes' => ['diff' => $diff],
        ]);
        session()->flash('success','Organization settings saved');
    }

    private function decodeJsonField($value)
    {
        if (! $value) return null;
        try { return json_decode($value, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) { return null; }
    }

    public function render()
    {
        return view('livewire.super.organization-settings')->layout('components.layouts.app');
    }
}
