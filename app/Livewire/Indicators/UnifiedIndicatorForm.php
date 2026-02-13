<?php

namespace App\Livewire\Indicators;

use App\Models\DOSTAgency;
use App\Models\AuditLog;
use App\Models\CategoryField;
use App\Models\Chapter;
use App\Models\IndicatorCategory;
use App\Models\IndicatorMandatoryAssignment;
use App\Models\Indicator as Objective;
use App\Models\Office;
use App\Models\Pillar;
use App\Models\Outcome;
use App\Models\Strategy;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DOSTAgency as Agency;

class UnifiedIndicatorForm extends Component
{
    use WithFileUploads;

    // Tab navigation & Visibility
    public int $currentTab = 1;
    public bool $showForm = false;

    // Category selection
    public ?string $selectedCategory = null;

    // Tab 1 - Common fields
    public array $form = [
        'id' => null,
        'status' => Objective::STATUS_DRAFT,
        'rejection_reason' => '',
        'remarks' => '',
        'admin_name' => 'PBBM',
        'year_start' => null,
        'year_end' => null,
        'indicator_description' => '',
        'baseline' => '',
        'target_value' => '',
        'mov' => '',
        'responsible_agency_id' => null,
        'reporting_agency_id' => null,
        'pillar_id' => null,
        'outcome_id' => null,
        'strategy_id' => null,
        'proof_file' => null,
    ];

    public array $breakdown = [];

    // Tab 2 - Dynamic fields
    public array $dynamicFields = [];
    public $categoryFields = [];

    // Strategic Plan Options
    public $pillars = [];
    public $outcomes = [];
    public $strategies = [];

    // Mandatory assignment properties
    public bool $is_mandatory = false;
    public array $mandatory_assignments = [];
    public string $assignment_type = 'all';
    public ?int $selected_office_id = null;
    public ?int $selected_agency_id = null;

    public function mount(): void
    {
        $this->form['year_start'] = now()->year;
        $this->form['year_end'] = null;
        $this->regenerateBreakdownRows();

        // Load Strategic Plan options
        $this->pillars = Pillar::where('is_active', true)->orderBy('value')->pluck('value', 'id')->toArray();
        $this->outcomes = Outcome::where('is_active', true)->orderBy('value')->pluck('value', 'id')->toArray();
        $this->strategies = Strategy::where('is_active', true)->orderBy('value')->pluck('value', 'id')->toArray();
    }

    // --- 1. CREATE ENTRY POINT ---
    #[On('open-unified-form')]
    public function openForm(bool $isMandatory = false, ?int $officeId = null, ?int $agencyId = null): void 
    {
        $this->resetForm();
        $this->is_mandatory = $isMandatory;

        if ($isMandatory && $officeId) {
            $office = Office::with('region')->find($officeId);
            if ($office) {
                $this->mandatory_assignments[] = [
                    'assignment_type' => 'office',
                    'region_id' => $office->region_id,
                    'office_id' => $office->id,
                    'agency_id' => null,
                    'office_name' => $office->name,
                    'agency_name' => null,
                ];
            }
        }

        if ($isMandatory && $agencyId) {
            $agency = DOSTAgency::find($agencyId);
            if ($agency) {
                $this->mandatory_assignments[] = [
                    'assignment_type' => 'agency',
                    'region_id' => null,
                    'office_id' => null,
                    'agency_id' => $agency->id,
                    'office_name' => null,
                    'agency_name' => $agency->acronym ?? $agency->name,
                ];
            }
        }

        $this->showForm = true;
        $this->currentTab = 1;
    }

    // --- 2. EDIT ENTRY POINT ---
    #[On('edit-indicator')]
    public function editIndicator(int $id): void
    {
        $this->resetForm();
        
        $objective = Objective::with(['mandatoryAssignments'])->find($id);
        
        if (!$objective) {
            $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
            return;
        }

        $this->form['id'] = $objective->id;
        $this->form['status'] = $objective->status; 
        $this->form['rejection_reason'] = $objective->pc_secretariat_remarks; 
        $this->form['remarks'] = $objective->remarks; 
        $this->form['admin_name'] = $objective->admin_name;

        // Parse target_period (e.g., "2023-2028")
        $parts = explode('-', $objective->target_period ?? '');
        $this->form['year_start'] = isset($parts[0]) && is_numeric($parts[0]) ? (int)trim($parts[0]) : null;
        $this->form['year_end']   = isset($parts[1]) && is_numeric($parts[1]) ? (int)trim($parts[1]) : null;

        $this->form['indicator_description'] = $objective->indicator;
        $this->form['baseline'] = $objective->baseline;
        $this->form['target_value'] = $objective->target_value;
        $this->form['mov'] = $objective->mov;
        
        $this->form['responsible_agency_id'] = DOSTAgency::where('name', $objective->responsible_agency)->first()?->id;
        $this->form['reporting_agency_id'] = DOSTAgency::where('name', $objective->reporting_agency)->first()?->id;

        // Load Strategic Plan values
        $this->form['pillar_id'] = $objective->pillar_id;
        $this->form['outcome_id'] = $objective->outcome_id;
        $this->form['strategy_id'] = $objective->strategy_id;

        $this->is_mandatory = (bool) $objective->is_mandatory;
        $this->selectedCategory = $objective->category;
        $this->loadCategoryFields(); 

        foreach ($this->categoryFields as $field) {
            $dbCol = $field['db_column'] ?? null;
            if ($dbCol === 'pc_secretariat_remarks') continue; 
            
            if ($dbCol && isset($objective->$dbCol)) {
                $this->dynamicFields[$field['field_name']] = $objective->$dbCol;
            }
        }

        $this->loadBreakdown($objective);

        $this->showForm = true;
        $this->currentTab = 1;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function updatedSelectedCategory(): void
    {
        $this->loadCategoryFields();
    }

    protected function loadCategoryFields(): void
    {
        $this->categoryFields = [];
        if (!$this->selectedCategory) return;

        $category = IndicatorCategory::where('slug', $this->selectedCategory)->first();

        if ($category) {
            $this->categoryFields = CategoryField::where('category_id', $category->id)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get()
                ->toArray();

            // Apply your Fix: Unbind admin note from user remarks
            foreach ($this->categoryFields as &$field) {
                if ($field['db_column'] === 'pc_secretariat_remarks') {
                    $field['db_column'] = 'remarks'; 
                }
            }

            foreach ($this->categoryFields as $field) {
                if (!isset($this->dynamicFields[$field['field_name']])) {
                    $this->dynamicFields[$field['field_name']] = '';
                }
            }
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['selectedCategory', 'currentTab', 'categoryFields', 'dynamicFields', 'breakdown', 'is_mandatory', 'mandatory_assignments']);
        $this->form = [
            'id' => null,
            'status' => Objective::STATUS_DRAFT,
            'rejection_reason' => '',
            'remarks' => '',
            'admin_name' => 'PBBM',
            'year_start' => now()->year,
            'year_end' => null,
            'indicator_description' => '',
            'baseline' => '',
            'target_value' => '',
            'mov' => '',
            'responsible_agency_id' => null,
            'reporting_agency_id' => null,
        ];
        $this->regenerateBreakdownRows();
    }

    protected function tab1Rules(): array
    {
        $rules = [
            'selectedCategory' => 'required|exists:indicator_categories,slug',
            'form.admin_name' => 'required|string|max:255',
            'form.year_start' => 'required|integer|min:2010|max:2100', // FIXED: Variable Name
            'form.indicator_description' => 'required|string|max:1000',
            'form.baseline' => 'required|string|max:255',
            'form.target_value' => 'required|numeric|min:0',
            'form.mov' => 'required|string|max:500',
            'form.responsible_agency_id' => 'required|exists:agencies,id',
            'form.reporting_agency_id' => 'required|exists:agencies,id',
            'form.remarks' => 'nullable|string|max:1000',
            'breakdown.*.target' => 'nullable|numeric|min:0', // FIXED: Table Validation + prevent negative values
            'breakdown.*.actual' => 'nullable|numeric|min:0',
        ];

        // Require proof when editing an approved indicator
        if ($this->form['id'] && isset($this->form['status']) && $this->form['status'] === Objective::STATUS_APPROVED) {
            $rules['form.proof_file'] = 'required|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
        }

        return $rules;
    }

    protected function buildDynamicValidationRules(): array
    {
        $rules = [];
        foreach ($this->categoryFields as $field) {
            $fieldRules = $field['is_required'] ? ['required'] : ['nullable'];
            if ($field['field_type'] === 'number') {
            $fieldRules[] = 'numeric';
            $fieldRules[] = 'min:0'; // Prevent negative values in dynamic number fields
        }
            elseif ($field['field_type'] === 'text') { $fieldRules[] = 'string'; $fieldRules[] = 'max:255'; }
            $rules['dynamicFields.' . $field['field_name']] = $fieldRules;
        }
        return $rules;
    }

    public function save(): void
    {
        $rules = array_merge($this->tab1Rules(), $this->buildDynamicValidationRules());
        $this->validate($rules);

        $payload = $this->mapFormToObjective();

        if ($this->form['id']) {
            $objective = Objective::find($this->form['id']);
            $this->audit('update', $objective, ['before' => $objective->toArray(), 'after' => $payload]);

            if ($objective->status === Objective::STATUS_REJECTED) {
                $payload['status'] = Objective::STATUS_DRAFT;
            }

            $objective->update($payload);

            // Handle proof upload when editing approved indicator
            if (($objective->status ?? null) === Objective::STATUS_APPROVED && isset($this->form['proof_file']) && $this->form['proof_file']) {
                $path = $this->form['proof_file']->store('proofs', 'public');
                \App\Models\Proof::create([
                    'objective_id' => $objective->id,
                    'file_path' => $path,
                    'file_name' => $this->form['proof_file']->getClientOriginalName(),
                    'file_type' => $this->form['proof_file']->getClientMimeType(),
                    'file_size' => $this->form['proof_file']->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }

            $message = 'Indicator updated successfully.';
        } else {
            $objective = Objective::create($payload);
            $this->saveMandatoryAssignments($objective->id);
            $this->audit('create', $objective, ['after' => $payload]);
            $message = 'Indicator created successfully.';
        }

        $this->dispatch('indicator-saved')->to(\App\Livewire\Dashboard\UnifiedDashboard::class);
        $this->dispatch('toast', message: $message, type: 'success');
        $this->closeForm();
    }

    protected function mapFormToObjective(): array
    {
        $responsibleAgency = DOSTAgency::find($this->form['responsible_agency_id']);
        $reportingAgency = DOSTAgency::find($this->form['reporting_agency_id']);

        $period = (string) $this->form['year_start'];
        if (!empty($this->form['year_end']) && $this->form['year_end'] != $this->form['year_start']) {
            $period .= '-' . $this->form['year_end'];
        }

        $targetsSeries = []; $actualsSeries = [];
        foreach ($this->breakdown as $row) {
            if ($row['target'] !== '' && $row['target'] !== null) {
                $targetsSeries[] = ['year' => (int)$row['year'], 'value' => (float)$row['target']];
            }
            if ($row['actual'] !== '' && $row['actual'] !== null) {
                $actualsSeries[] = ['year' => (int)$row['year'], 'value' => (float)$row['actual']];
            }
        }

        $payload = [
            'category' => $this->selectedCategory,
            'admin_name' => $this->form['admin_name'],
            'target_period' => $period,
            'indicator' => $this->form['indicator_description'],
            'baseline' => $this->form['baseline'],
            'target_value' => $this->form['target_value'],
            'mov' => $this->form['mov'],
            'responsible_agency' => $responsibleAgency?->name,
            'reporting_agency' => $reportingAgency?->name,
            'remarks' => $this->form['remarks'],
            'updated_by' => Auth::id(),
            'is_mandatory' => $this->is_mandatory,
            'annual_plan_targets_series' => $targetsSeries,
            'accomplishments_series' => $actualsSeries,
            'pillar_id' => $this->form['pillar_id'],
            'outcome_id' => $this->form['outcome_id'],
            'strategy_id' => $this->form['strategy_id'],
        ];

        if (!$this->form['id']) {
            $payload['submitted_by_user_id'] = Auth::id();
            $payload['status'] = Objective::STATUS_DRAFT;
            $payload['office_id'] = Auth::user()->office_id;
            $payload['region_id'] = Auth::user()->region_id; 
            
            $payload['chapter_id'] = $this->getChapterIdForCategory();
            if ($this->selectedCategory === 'strategic_plan') $payload['sp_id'] = $this->nextSpId();
            elseif ($this->selectedCategory === 'prexc') $payload['prexc_code'] = $this->nextPrexcCode();
        }

        foreach ($this->categoryFields as $field) {
            $value = $this->dynamicFields[$field['field_name']] ?? null;
            if ($value !== null) $payload[$field['db_column']] = $value;
        }

        return $payload;
    }

    protected function getChapterIdForCategory(): int
    {
        $categoryMap = [
            'strategic_plan' => ['code' => 'SP', 'title' => 'Strategic Plan'],
            'prexc' => ['code' => 'PX', 'title' => 'PREXC'],
            'pdp' => ['code' => 'PDP', 'title' => 'PDP'],
            'agency_specifics' => ['code' => 'AS', 'title' => 'Agency Specifics'],
        ];

        $config = $categoryMap[$this->selectedCategory] ?? ['code' => 'GEN', 'title' => 'General'];

        if ($this->selectedCategory === 'pdp' && !empty($this->dynamicFields['chapter_name'])) {
            $chapter = Chapter::firstOrCreate(
                ['category' => 'pdp', 'code' => $this->dynamicFields['chapter_name']],
                ['title' => $this->dynamicFields['chapter_name'], 'outcome' => $this->dynamicFields['chapter_name'], 'is_active' => true]
            );
            return $chapter->id;
        }

        $chapter = Chapter::where('category', $this->selectedCategory)->orderBy('sort_order')->first();
        if (!$chapter) {
            $chapter = Chapter::create([
                'category' => $this->selectedCategory,
                'code' => $config['code'],
                'title' => $config['title'],
                'outcome' => $config['title'],
                'is_active' => true,
            ]);
        }
        return $chapter->id;
    }

    protected function nextSpId(): int { return (int) (Objective::where('category', 'strategic_plan')->max('sp_id') ?? 0) + 1; }

    protected function nextPrexcCode(): string
    {
        $year = $this->form['year_start']; // FIXED: Variable Name
        $maxCode = Objective::where('category', 'prexc')->where('prexc_code', 'like', "PX-{$year}-%")->max('prexc_code');
        $seq = $maxCode ? ((int)explode('-', $maxCode)[2] + 1) : 1;
        return sprintf('PX-%d-%04d', $year, $seq);
    }

    protected function saveMandatoryAssignments(int $objectiveId): void
    {
        IndicatorMandatoryAssignment::where('objective_id', $objectiveId)->delete();
        if ($this->is_mandatory) {
            foreach ($this->mandatory_assignments as $assignment) {
                IndicatorMandatoryAssignment::create([
                    'objective_id' => $objectiveId,
                    'assignment_type' => $assignment['assignment_type'] ?? 'all',
                    'region_id' => $assignment['region_id'] ?? null,
                    'office_id' => $assignment['office_id'] ?? null,
                    'agency_id' => $assignment['agency_id'] ?? null,
                ]);
            }
        }
    }

    protected function audit(string $action, Objective $obj, array $changes): void
    {
        try {
            AuditLog::create([
                'actor_user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => 'Objective',
                'entity_id' => (string) $obj->id,
                'changes' => $changes,
            ]);
        } catch (\Throwable $e) {}
    }

    public function render()
    {
        $user = Auth::user();
        return view('livewire.indicators.unified-indicator-form', [
            'categories' => IndicatorCategory::where('is_active', true)->visibleTo($user)->orderBy('display_order')->get(),
            'agencies' => DOSTAgency::where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
            'pillars' => Pillar::where('is_active', true)->orderBy('value')->pluck('value', 'id')->toArray(),
            'outcomes' => Outcome::where('is_active', true)->orderBy('value')->pluck('value', 'id')->toArray(),
            'strategies' => Strategy::where('is_active', true)->orderBy('value')->pluck('value', 'id')->toArray(),
        ]);
    }

    public function updatedFormYearStart() { $this->regenerateBreakdownRows(); }
    public function updatedFormYearEnd() { $this->regenerateBreakdownRows(); }

    private function loadBreakdown($obj)
    {
        $this->breakdown = [];
        $s = (int) $this->form['year_start'];
        $e = (int) ($this->form['year_end'] ?: $s);
        $targets = collect($obj->annual_plan_targets_series ?? []);
        $actuals = collect($obj->accomplishments_series ?? []);

        for ($y = $s; $y <= $e; $y++) {
            $t = $targets->firstWhere('year', $y)['value'] ?? '';
            $a = $actuals->firstWhere('year', $y)['value'] ?? '';
            $this->breakdown[] = ['year' => $y, 'target' => $t, 'actual' => $a];
        }
    }

    private function regenerateBreakdownRows()
    {
        $start = (int) ($this->form['year_start'] ?? 0);
        $end = (int) ($this->form['year_end'] ?? 0);
        if ($start > 0) {
            if ($end < $start) $end = $start;
            $newBreakdown = [];
            for ($y = $start; $y <= $end; $y++) {
                $existing = collect($this->breakdown)->firstWhere('year', $y);
                $newBreakdown[] = ['year' => $y, 'target' => $existing['target'] ?? '', 'actual' => $existing['actual'] ?? ''];
            }
            $this->breakdown = $newBreakdown;
        }
    }

    // Chart data generation for tracking progress
    public function getChartDataProperty(): array
    {
        if (empty($this->breakdown)) {
            return ['data' => [], 'max' => 1];
        }

        $chartData = [];
        $maxVal = 0;

        foreach ($this->breakdown as $row) {
            $target = is_numeric($row['target']) ? (float)$row['target'] : 0;
            $actual = is_numeric($row['actual']) ? (float)$row['actual'] : 0;
            
            if ($target > $maxVal) $maxVal = $target;
            if ($actual > $maxVal) $maxVal = $actual;

            $chartData[] = [
                'label' => (string)$row['year'],
                'target' => $target,
                'actual' => $actual,
            ];
        }

        // Avoid division by zero
        if ($maxVal === 0) $maxVal = 1;

        return ['data' => $chartData, 'max' => $maxVal];
    }
}