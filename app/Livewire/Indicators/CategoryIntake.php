<?php

namespace App\Livewire\Indicators;

use Livewire\Component;
use App\Models\IndicatorTemplate;
use App\Models\Chapter; 
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryIntake extends Component
{
    public $category = '';
    public $search = '';
    public $chapter = null; // selected chapter/grouping ID
    public ?string $indicator = null;
    public $selectedId = null;
    public $selected;
    protected $queryString = [
        'category' => ['except' => ''],
        'chapter'  => ['except' => null],
        'search'   => ['except' => ''],
    ];

    public $categories = [
        'strategic_plan' => 'Strategic Plan',
        'pdp' => 'PDP',
        'prexc' => 'PREXC',
        'agency_specifics' => 'Agency Specifics',
    ];

    // Categories that use an intermediate "Chapters" step
    public array $categoriesWithGrouping = [
        'strategic_plan',
        'pdp',
    ];

    // Create indicator inline (super admin only)
    public bool $showCreate = false;
    public $code = '';
    public $name = '';
    public $description = '';
    public $allowed_value_type = 'value';
    public bool $baseline_required = false;
    public bool $mov_required = false;
    public bool $is_active = true;

    // Chapters (Strategic Plan) / Generic Grouping Modal
    public bool $showChapterModal = false;
    public $ch_title = '';
    public $ch_description = '';

    public function selectCategory(string $key)
    {
        $this->category = $key;
        $this->chapter = null;
        $this->indicator = null;
        $this->selectedId = null;
        $this->selected = null;
        $this->search = '';
    }

    public function mount(?string $category = null): void
    {
        if ($category && isset($this->categories[$category])) {
            $this->category = $category;
        }
    }

    public function pick(int $id): void
    {
        $this->selectedId = $id;
        $this->selected = IndicatorTemplate::find($id);
    }

    public function selectChapter(?int $id = null): void
    {
        $this->chapter = $id;
        $this->indicator = null;
    }

    public function selectIndicator(string $name): void
    {
        $this->indicator = $name;
    }

    public function clearIndicator(): void
    {
        $this->indicator = null;
    }
    
    // Computed property to check if we should display the grouping list
    public function getShouldShowGroupingProperty(): bool
    {
        // This logic now ensures every selected category shows the grouping step first
        return in_array($this->category, $this->categoriesWithGrouping) && $this->chapter === null;
    }

    public function getListProperty(): Collection
    {
        if (!$this->category) { return collect(); }
        $q = IndicatorTemplate::query()->where('is_active', true);

        if (Schema::hasColumn('indicator_templates', 'category')) {
            $q->where('category', $this->category);
        }

        // Generalized grouping filter logic: applies to all categories now
        if (in_array($this->category, $this->categoriesWithGrouping) && $this->chapter) {
            // Assuming the column is 'chapter_id' for all categories for now
            $id_column = 'chapter_id';

            if (Schema::hasColumn('indicator_templates', $id_column)) {
                $q->where($id_column, $this->chapter);
            }
        }

        if ($this->search) {
            $s = '%'.trim($this->search).'%';
            $q->where(function($x) use ($s){
                $x->where('code','like',$s)->orWhere('name','like',$s)->orWhere('description','like',$s);
            });
        }

        // Reduced limit for faster loading
        return $q->orderBy('name')->limit(100)->get();
    }

    public function applySearch(): void
    {
        $this->search = trim((string)$this->search);
    }

    // Generalized Groupings property
    public function getGroupingsProperty(): Collection
    {
        // Fetch groups for all categories in $categoriesWithGrouping
        if (!in_array($this->category, $this->categoriesWithGrouping)) return collect();

        // **NOTE**: You must ensure your Chapter model/table has entries created
        // with the category names 'pdp', 'prexc', and 'agency_specifics'
        // for this to display groupings for those categories.

        // Cache chapters for 5 minutes to improve performance
        return cache()->remember(
            "chapters_{$this->category}",
            now()->addMinutes(5),
            fn() => Chapter::query()
                ->where('category', $this->category)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(100)
                ->get()
        );
    }

    public function openChapterModal(): void
    {
        if (!in_array($this->category, $this->categoriesWithGrouping)) return;
        if (!auth()->user()?->isSuperAdmin()) return;
        $this->showChapterModal = true;
        $this->resetChapterForm();
    }

    public function cancelChapter(): void
    {
        $this->showChapterModal = false;
    }

    public function saveChapter(): void
    {
        if (!auth()->user()?->isSuperAdmin() || !in_array($this->category, $this->categoriesWithGrouping)) return;

        $this->validate([
            'ch_title' => ['required','string','max:255'],
            'ch_description' => ['nullable','string'],
        ]);

        Chapter::create([
            'category' => $this->category, // Use current category for the new grouping
            'code' => null,
            'title' => $this->ch_title,
            'outcome' => $this->ch_title,
            'description' => $this->ch_description ?: null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        // Clear the chapters cache so new chapter appears immediately
        cache()->forget("chapters_{$this->category}");

        $this->showChapterModal = false;
        session()->flash('success', ($this->category === 'strategic_plan' ? 'Chapter' : 'Grouping') . ' created.');
    }

    private function resetChapterForm(): void
    {
        $this->ch_title = '';
        $this->ch_description = '';
    }

    public function startCreate(): void
    {
        if (!$this->category) return;
        $this->showCreate = true;
        $this->resetCreateForm();
    }

    public function cancelCreate(): void
    {
        $this->showCreate = false;
    }

    public function saveIndicator(): void
    {
        // Basic validation mirroring the admin catalog
        $this->validate([
            'code' => ['required','string','max:50', Rule::unique('indicator_templates','code')],
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'allowed_value_type' => ['required', Rule::in(['percent','count','currency','value'])],
            'baseline_required' => ['boolean'],
            'mov_required' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $payload = [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'allowed_value_type' => $this->allowed_value_type,
            'baseline_required' => $this->baseline_required,
            'mov_required' => $this->mov_required,
            'is_active' => $this->is_active,
        ];
        
        if (Schema::hasColumn('indicator_templates','category')) {
            $payload['category'] = $this->category ?: 'agency_specifics';
        }

        // Apply grouping ID if applicable (always applicable now)
        if (in_array($this->category, $this->categoriesWithGrouping) && $this->chapter) {
             if (Schema::hasColumn('indicator_templates','chapter_id')) {
                $payload['chapter_id'] = $this->chapter;
            }
        }
        
        $tpl = IndicatorTemplate::create($payload);

        // Minimal audit
        \App\Models\AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'IndicatorTemplate',
            'entity_id' => (string)$tpl->id,
            'changes' => ['diff' => [
                'code' => ['before'=>null,'after'=>$tpl->code],
                'name' => ['before'=>null,'after'=>$tpl->name],
                'category' => ['before'=>null,'after'=>$tpl->category],
                'grouping_id' => ['before'=>null,'after'=>$this->chapter],
            ]],
        ]);

        $this->selectedId = $tpl->id;
        $this->selected = $tpl;
        $this->showCreate = false;
        session()->flash('success','Indicator created. You can now add information.');
    }

    private function resetCreateForm(): void
    {
        $this->reset(['code','name','description','allowed_value_type','baseline_required','mov_required','is_active']);
        $this->allowed_value_type = 'value';
        $this->baseline_required = false;
        $this->mov_required = false;
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.indicators.category-intake')->layout('components.layouts.app');
    }
}
