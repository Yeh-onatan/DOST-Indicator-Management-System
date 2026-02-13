<?php

namespace App\Livewire\Indicators;

use Livewire\Component;
use App\Models\IndicatorTemplate;
use App\Models\Chapter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Library extends Component
{
    public string $category = '';
    public $chapter = null; // int|null
    public bool $manage = false;
    public bool $showEdit = false;
    public ?int $editingId = null;
    public string $search = '';

    public function mount(string $category, int $chapter): void
    {
        $this->category = $category;
        $this->chapter = $chapter;
    }

    public function getListProperty()
    {
        $base = IndicatorTemplate::query()->where('is_active', true);
        if ($this->category && Schema::hasColumn('indicator_templates', 'category')) {
            $base->where('category', $this->category);
        }

        // Filter by chapter_id for ALL categories, not just strategic_plan
        if ($this->chapter && Schema::hasColumn('indicator_templates', 'chapter_id')) {
            $base->where('chapter_id', $this->chapter);
        }

        // Search filter
        if ($this->search) {
            $base->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $base->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.indicators.library', [
            'chapterModel' => $this->chapter ? Chapter::find($this->chapter) : null,
        ])->layout('components.layouts.app');
    }

    // --- Create Objective/Result (Indicator Template) modal ---
    public bool $showCreate = false;
    public $code = '';
    public $name = '';
    public $description = '';
    public $allowed_value_type = 'value';
    public bool $baseline_required = false;
    public bool $mov_required = false;
    public bool $is_active = true;

    public function startCreate(): void
    {
        if (!$this->category) return;
        $this->resetCreateForm();
        $this->showCreate = true;
    }

    public function cancelCreate(): void
    {
        $this->showCreate = false;
    }

    public function saveIndicator(): void
    {
        $this->validate([
            'code' => ['nullable','string','max:50', Rule::unique('indicator_templates','code')],
            'name' => ['required','string','max:255'],
            // Value type no longer chosen here; default to 'value'
            'is_active' => ['boolean'],
        ]);

        $resolvedCode = $this->resolveCode();

        $payload = [
            'code' => $resolvedCode,
            'name' => $this->name,
            'description' => null,
            'allowed_value_type' => 'value',
            // baseline/mov flags are managed during Add Information, keep defaults false here
            'baseline_required' => false,
            'mov_required' => false,
            'is_active' => $this->is_active,
        ];
        if (Schema::hasColumn('indicator_templates','category')) {
            $payload['category'] = $this->category ?: 'agency_specifics';
        }
        if ($this->chapter && Schema::hasColumn('indicator_templates','chapter_id')) {
            $payload['chapter_id'] = $this->chapter;
        }

        $tpl = IndicatorTemplate::create($payload);

        if (class_exists('App\\Models\\AuditLog')) {
            \App\Models\AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'IndicatorTemplate',
                'entity_id' => (string)$tpl->id,
                'changes' => ['diff' => [
                    'code' => ['before'=>null,'after'=>$tpl->code],
                    'name' => ['before'=>null,'after'=>$tpl->name],
                    'category' => ['before'=>null,'after'=>$tpl->category],
                ]],
            ]);
        }

        $this->showCreate = false;
        session()->flash('success','Indicator created.');
    }

    private function resetCreateForm(): void
    {
        $this->reset(['code','name','description','allowed_value_type','baseline_required','mov_required','is_active']);
        $this->allowed_value_type = 'value';
        $this->baseline_required = false;
        $this->mov_required = false;
        $this->is_active = true;
    }

    public function deleteTemplate(int $id): void
    {
        if (!auth()->user()?->isSuperAdmin()) {
            session()->flash('error', 'Only Super Admin can delete indicators.');
            return;
        }
        if ($tpl = IndicatorTemplate::find($id)) {
            try {
                $tpl->delete();
                session()->flash('success', 'Indicator deleted.');
            } catch (\Throwable $e) {
                session()->flash('error', 'Delete failed: '.$e->getMessage());
            }
        }
    }

    public function toggleManage(): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;
        $this->manage = !$this->manage;
    }

    public function startEdit(int $id): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;
        $tpl = IndicatorTemplate::find($id);
        if (!$tpl) return;
        $this->editingId = $tpl->id;
        $this->code = $tpl->code;
        $this->name = $tpl->name;
        $this->description = $tpl->description;
        $this->allowed_value_type = $tpl->allowed_value_type ?? 'value';
        $this->baseline_required = (bool)($tpl->baseline_required ?? false);
        $this->mov_required = (bool)($tpl->mov_required ?? false);
        $this->is_active = (bool)($tpl->is_active ?? true);
        $this->showEdit = true;
    }

    public function cancelEdit(): void
    {
        $this->showEdit = false;
        $this->editingId = null;
    }

    public function updateTemplate(): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;
        if (!$this->editingId) return;

        $this->validate([
            'code' => ['nullable','string','max:50', Rule::unique('indicator_templates','code')->ignore($this->editingId)],
            'name' => ['required','string','max:255'],
            // don’t edit value type here
            'is_active' => ['boolean'],
        ]);

        $tpl = IndicatorTemplate::find($this->editingId);
        if (!$tpl) return;

        $resolvedCode = $this->resolveCode($this->editingId);

        try {
            $tpl->update([
                'code' => $resolvedCode,
                'name' => $this->name,
                'description' => null,
                // keep existing allowed_value_type/baseline/mov; only toggle active here
                'is_active' => (bool)$this->is_active,
            ]);
            $this->showEdit = false;
            $this->editingId = null;
            session()->flash('success', 'Indicator updated.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Update failed: '.$e->getMessage());
        }
    }

    /**
     * Resolve a usable indicator code. If the user left the field blank,
     * derive one from the name and guarantee uniqueness.
     */
    protected function resolveCode(?int $ignoreId = null): string
    {
        $code = trim((string)$this->code);
        if ($code !== '') {
            return $code;
        }

        $base = Str::upper(Str::slug($this->name, '-'));
        if ($base === '') {
            $base = 'IND';
        }

        $candidate = Str::limit($base, 50, '');
        $suffix = 1;
        while (
            IndicatorTemplate::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('code', $candidate)
                ->exists()
        ) {
            $suffix++;
            $candidate = Str::limit($base.'-'.$suffix, 50, '');
        }

        // Update the Livewire property so the user sees the generated value next time.
        $this->code = $candidate;

        return $candidate;
    }
}
