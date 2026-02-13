<?php

namespace App\Livewire\Indicators;

use Livewire\Component;
use App\Models\Chapter;

class ChaptersIndex extends Component
{
    public string $category = 'strategic_plan';
    public bool $manage = false;

    // Create Chapter modal state
    public bool $showCreate = false;
    public $code = '';
    public $title = '';
    public $description = '';
    public $sort_order = null; // int|null
    public bool $is_active = true;

    // Edit Chapter modal state
    public bool $showEdit = false;
    public ?int $editingId = null;

    public function mount(string $category): void
    {
        $this->category = $category ?: 'strategic_plan';
        // Redirect Strategic Plan to its dedicated page (no chapters UI)
        if ($this->category === 'strategic_plan') {
            // NOTE: In Livewire, return a redirect rather than calling ->send()
            $this->redirectRoute('strategic-plan.index');
            return;
        }
    }

    public function getListProperty()
    {
        return Chapter::query()
            ->where('category', $this->category)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function render()
    {
        return view('livewire.indicators.chapters-index')->layout('components.layouts.app');
    }

    public function delete(int $id): void
    {
        if (!auth()->user()?->isSuperAdmin()) {
            session()->flash('error', 'Only Super Admin can delete chapters.');
            return;
        }
        if ($ch = \App\Models\Chapter::find($id)) {
            try {
                $ch->delete();
                session()->flash('success', 'Chapter deleted.');
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

    public function startCreate(): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;
        $this->resetCreateForm();
        $this->showCreate = true;
    }

    public function cancelCreate(): void
    {
        $this->showCreate = false;
    }

    public function saveChapter(): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;

        $this->validate([
            'title' => ['required','string','max:255'],
            'code' => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['boolean'],
        ]);

        // Default sort_order to last+1 within this category when not provided
        $order = $this->sort_order;
        if ($order === null) {
            $order = (int) (\App\Models\Chapter::query()
                ->where('category', $this->category)
                ->max('sort_order') ?? 0) + 1;
        }

        try {
            \App\Models\Chapter::create([
                'category' => $this->category,
                'code' => $this->code ?: null,
                'title' => $this->title,
                // Outcome column is required in DB; reuse title to keep it non-empty now that the field is hidden.
                'outcome' => $this->title,
                'description' => $this->description ?: null,
                'sort_order' => $order,
                'is_active' => (bool)$this->is_active,
            ]);

            $this->showCreate = false;
            session()->flash('success', 'Chapter created.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Create failed: '.$e->getMessage());
        }
    }

    private function resetCreateForm(): void
    {
        $this->reset(['code','title','description','sort_order']);
        $this->is_active = true;
    }

    public function startEdit(int $id): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;
        $ch = \App\Models\Chapter::find($id);
        if (!$ch) return;
        $this->editingId = $ch->id;
        $this->code = $ch->code;
        $this->title = $ch->title;
        $this->description = $ch->description;
        $this->sort_order = $ch->sort_order;
        $this->is_active = (bool)$ch->is_active;
        $this->showEdit = true;
    }

    public function cancelEdit(): void
    {
        $this->showEdit = false;
        $this->editingId = null;
    }

    public function updateChapter(): void
    {
        if (!auth()->user()?->isSuperAdmin()) return;
        if (!$this->editingId) return;

        $this->validate([
            'title' => ['required','string','max:255'],
            'code' => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['boolean'],
        ]);

        $ch = \App\Models\Chapter::find($this->editingId);
        if (!$ch) return;

        try {
            $payload = [
                'code' => $this->code ?: null,
                'title' => $this->title,
                // Outcome column is required in DB; reuse title to keep it non-empty.
                'outcome' => $this->title,
                'description' => $this->description ?: null,
                'is_active' => (bool)$this->is_active,
            ];
            if ($this->sort_order !== null) {
                $payload['sort_order'] = (int)$this->sort_order;
            }
            $ch->update($payload);
            $this->showEdit = false;
            $this->editingId = null;
            session()->flash('success', 'Chapter updated.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Update failed: '.$e->getMessage());
        }
    }
}
