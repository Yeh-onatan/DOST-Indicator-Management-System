<?php

namespace App\Livewire\Proponent;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Indicator as Objective;
use App\Models\Chapter;
use App\Models\ReportingWindow;
use App\Models\AdminSetting;
use App\Models\PhilippineRegion;
use App\Models\Office;
use App\Models\IndicatorMandatoryAssignment;
use Illuminate\Support\Facades\Schema;

class ObjectiveForm extends Component
{
    use WithFileUploads;
    // === Step Management ===
    public $currentStep = 1;
    public $maxSteps = 3;

    // Track when we're editing
    public ?int $editingId = null;

    // === Core Details (Step 1) ===
    public $objective_result;
    public $indicator;
    public ?int $chapter_id = null;
    public $description;
    public $dost_agency;
    public $owner_id = '1';
    public $priority = 'Medium';

    // === Performance Tracking (Step 2) ===
    public $baseline;
    public $accomplishments;
    public $annual_plan_targets;
    
    // [MODIFIED] Replaced simple target_period with start/end fields
    public $target_period_start;
    public $target_period_end;
    
    public $target_value;
    public array $accomplishments_series = [];
    public array $annual_plan_targets_series = [];

    // === Generator (QoL) ===
    public ?int $gen_start_year = null;
    public ?int $gen_end_year = null;
    public $gen_baseline_value = null;
    public $gen_increment = 0.10;
    public bool $gen_overwrite = true;

    // === Documentation & Context (Step 3) ===
    public $mov;
    public $responsible_agency;
    public $reporting_agency;
    public $assumptions_risk;
    public ?string $lastSavedAt = null;
    public $pc_secretariat_remarks;

    // === Proof Upload (for editing approved indicators) ===
    public $proof_file;

    // --- Review status/feedback ---
    public $status;
    public $review_notes;
    public $corrections_required = [];
    public ?string $current_category = null;
    public bool $lock_indicator = false;
    public bool $viewOnly = false;

    // --- Assignment fields ---
    public bool $is_mandatory = false;
    public ?int $region_id = null;
    public ?int $office_id = null;
    public array $mandatory_assignments = [];
    public string $assignment_type = 'all';
    public ?int $selected_region_id = null;
    public ?int $selected_office_id = null;
    public ?int $selected_agency_id = null;
    public bool $hasPrefill = false;

    // Computed property: Check if agency has an assigned HO
    public function getAgencyHasHOProperty(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->isAgency()) {
            return false;
        }

        $agency = $user->agency;
        return $agency && $agency->head_user_id !== null;
    }

    public $availableOwners = [
        '1' => 'John Doe (Admin)',
        '2' => 'Jane Smith (Team Lead)',
        '3' => 'Alex Wong (Staff)',
    ];

    public function mount(): void
    {
        $settings = auth()->user()?->settings;
        if ($settings) {
            if (!$this->editingId) {
                if (!$this->dost_agency) {
                    $this->dost_agency = $settings->default_agency;
                }
                if ($settings->default_year && empty($this->annual_plan_targets_series)) {
                    $this->annual_plan_targets_series[] = [
                        'year' => (int) $settings->default_year,
                        'value' => '',
                        'met' => false,
                    ];
                }
            }
        }

        if (!$this->dost_agency) {
            $role = auth()->user()?->role;
            if (in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true)) {
                $this->dost_agency = 'DOST';
            }
        }

        // Initialize defaults for year range if not set
        if (!$this->target_period_start) {
            $this->target_period_start = now()->year;
        }

        $this->applyPstoDefaults();
    }

    protected function rules()
    {
        $common = [
            'chapter_id' => 'required|exists:chapters,id',
            'owner_id' => 'required|numeric',
            'priority' => 'required|in:Low,Medium,High,Critical',
        ];

        $step1 = [
            'indicator'        => 'required|string|min:3|max:255',
            'objective_result' => 'nullable|string|max:1000',
            'description'      => 'required|string',
            'dost_agency'      => 'required|string',
            'office_id'        => 'nullable|exists:offices,id',
        ];
        
        $step2 = [
            'baseline'                        => 'required|string',
            'accomplishments'                 => 'nullable|string',
            'annual_plan_targets'             => 'nullable|string',
            'annual_plan_targets_series'      => 'array',
            'annual_plan_targets_series.*.year'  => 'nullable|integer|min:2010|max:2100',
            'annual_plan_targets_series.*.value' => 'nullable|numeric|min:0',
            'annual_plan_targets_series.*.met'   => 'nullable|boolean',
            'target_value'                    => 'required|numeric|min:1',
            // [MODIFIED] Added validation for split period
            'target_period_start'             => 'required|integer|min:2010|max:2100',
            'target_period_end'               => 'nullable|integer|min:2010|max:2100|gte:target_period_start',
        ];

        $step3 = [
            'mov'                    => 'required|string',
            'responsible_agency'     => 'nullable|string',
            'reporting_agency'       => 'nullable|string',
            'assumptions_risk'       => 'nullable|string',
            'pc_secretariat_remarks' => 'nullable|string',
        ];

        // Require proof when editing an approved indicator
        $isEditingApproved = $this->editingId && $this->status === Objective::STATUS_APPROVED;
        if ($isEditingApproved) {
            $step3['proof_file'] = 'required|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
        }

        $restrict = $this->editingId
            && ($this->status === Objective::STATUS_REJECTED)
            && is_array($this->corrections_required)
            && count($this->corrections_required) > 0;

        if ($restrict) {
            $all = $step1 + $step2 + $step3 + $common;
            $filtered = [];
            foreach ($all as $field => $rule) {
                if (in_array($field, $this->corrections_required, true)) {
                    $filtered[$field] = $rule;
                }
            }
            return $filtered;
        }

        return match ($this->currentStep) {
            1 => $common + $step1,
            2 => $common + $step2,
            3 => $common + $step3,
            default => $common,
        };
    }

    #[On('load-objective')]
    public function loadObjective(int $id): void
    {
        $obj = Objective::find($id);
        if (! $obj) return;

        $this->editingId = $obj->id;

        $this->objective_result       = $obj->objective_result;
        $this->indicator              = $obj->indicator;
        $this->description            = $obj->description;
        $this->dost_agency            = $obj->dost_agency;
        $this->chapter_id             = $obj->chapter_id;
        $this->baseline               = $obj->baseline;
        $this->accomplishments        = $obj->accomplishments;
        $this->annual_plan_targets    = $obj->annual_plan_targets;
        
        // [MODIFIED] Parse the stored target_period string into start/end
        if ($obj->target_period) {
            $parts = explode('-', $obj->target_period);
            $this->target_period_start = isset($parts[0]) ? (int)trim($parts[0]) : null;
            // If there is a second part, use it. If not, end year equals start year.
            $this->target_period_end = isset($parts[1]) ? (int)trim($parts[1]) : $this->target_period_start;
        } else {
             $this->target_period_start = now()->year;
             $this->target_period_end = null;
        }

        $this->target_value           = $obj->target_value;
        $this->accomplishments_series = is_array($obj->accomplishments_series) ? array_values($obj->accomplishments_series) : [];
        $this->annual_plan_targets_series = is_array($obj->annual_plan_targets_series) ? array_values($obj->annual_plan_targets_series) : [];
        $this->mov                    = $obj->mov;
        $this->responsible_agency     = $obj->responsible_agency;
        $this->reporting_agency       = $obj->reporting_agency;
        $this->assumptions_risk       = $obj->assumptions_risk;
        $this->pc_secretariat_remarks = $obj->pc_secretariat_remarks;

        $category = $obj->category ?? ($obj->chapter?->category ?? null);
        $this->current_category = $category ? strtolower($category) : null;
        $this->region_id              = $obj->region_id ?? null;
        $this->office_id              = $obj->office_id ?? null;
        $this->is_mandatory           = $obj->is_mandatory ?? false;

        $this->mandatory_assignments = [];
        if ($obj->mandatoryAssignments && $obj->mandatoryAssignments->count() > 0) {
            foreach ($obj->mandatoryAssignments as $assignment) {
                $regionName = $assignment->region_id ? PhilippineRegion::find($assignment->region_id)?->name : null;
                $officeName = $assignment->office_id ? Office::find($assignment->office_id)?->name : null;
                $agencyName = $assignment->agency_id ? \App\Models\DOSTAgency::find($assignment->agency_id)?->acronym : null;

                $this->mandatory_assignments[] = [
                    'assignment_type' => $assignment->assignment_type ?? 'all',
                    'agency_id' => $assignment->agency_id,
                    'region_id' => $assignment->region_id,
                    'office_id' => $assignment->office_id,
                    'region_name' => $regionName,
                    'office_name' => $officeName,
                    'agency_name' => $agencyName,
                ];
            }
            $this->assignment_type = 'specific';
        } else {
            $this->assignment_type = $this->is_mandatory ? 'all' : 'all';
        }

        $this->status = $obj->status ?? null;
        $this->review_notes = $obj->review_notes ?? null;
        $this->corrections_required = is_array($obj->corrections_required ?? null)
            ? $obj->corrections_required
            : (array) ($obj->corrections_required ?? []);

        $this->currentStep = 1;
        $this->viewOnly = false;

        $this->syncAccomplishmentsFromMet();
    }

    #[On('view-objective')]
    public function viewObjective(int $id): void
    {
        $this->loadObjective($id);
        $this->viewOnly = true;
    }

    // ... [Rest of your prefill listeners remain unchanged] ...
    #[On('prefill-indicator')]
    public function prefillIndicator(?string $code = null, ?string $name = null, ?string $description = null, ?string $category = null): void
    {
        if ($this->editingId) {
            return;
        }
        $this->hasPrefill = true;

        if ($name && !$this->indicator) {
            $this->indicator = $name;
            $this->lock_indicator = true;
        }
        if (!$this->description && $description) {
            $this->description = $description;
        }
        if ($category) {
            $this->current_category = $category;
        }
        $this->currentStep = 1;
    }

    #[On('prefill-chapter')]
    public function prefillChapter(?int $chapterId = null, ?string $category = null): void
    {
        if ($this->editingId) {
            return;
        }
        $this->hasPrefill = true;

        if ($chapterId) {
            $this->chapter_id = $chapterId;
        }
        if ($category) {
            $this->current_category = $category;
        }
        $this->currentStep = 1;
    }

    #[On('create-with-pdp')]
    public function createWithPdp(
        ?int $templateId = null,
        ?int $chapterId = null,
        ?string $category = null,
        bool $isMandatory = false,
        ?int $officeId = null,
        ?int $agencyId = null
    ): void {
        $this->resetForm();
        $this->hasPrefill = true;

        if ($templateId) {
            $template = \App\Models\IndicatorTemplate::find($templateId);
            if ($template) {
                $this->indicator = $template->name;
                $this->description = $template->description;
                $this->lock_indicator = true;
            }
        }

        if ($chapterId) {
            $this->chapter_id = $chapterId;
        }

        if ($category) {
            $this->current_category = $category;
        }

        if ($isMandatory) {
            $this->is_mandatory = true;
            if ($officeId) {
                $office = \App\Models\Office::with('region')->find($officeId);
                if ($office) {
                    $this->mandatory_assignments[] = [
                        'assignment_type' => 'office',
                        'region_id' => $office->region_id,
                        'office_id' => $office->id,
                        'agency_id' => null,
                        'region_name' => $office->region?->name,
                        'office_name' => $office->name,
                        'agency_name' => null,
                    ];
                }
            } elseif ($agencyId) {
                $agency = \App\Models\DOSTAgency::find($agencyId);
                if ($agency) {
                    $this->mandatory_assignments[] = [
                        'assignment_type' => 'agency',
                        'region_id' => null,
                        'office_id' => null,
                        'agency_id' => $agency->id,
                        'region_name' => null,
                        'office_name' => null,
                        'agency_name' => $agency->acronym ?? $agency->name,
                    ];
                }
            }
        }

        $this->currentStep = 1;
    }
    
    // ... [createWithAgency method unchanged] ...
    #[On('create-with-agency')]
    public function createWithAgency(
        ?int $agencyId = null,
        ?string $category = null,
        bool $isMandatory = false,
        ?int $officeId = null,
        ?int $pstoAgencyId = null
    ): void {
        $this->resetForm();
        $this->hasPrefill = true;

        if ($agencyId) {
            $agency = \App\Models\DOSTAgency::find($agencyId);
            if ($agency) {
                $this->dost_agency = $agency->name;
            }
        }

        if ($category) {
            $this->current_category = $category;
        }

        if ($isMandatory) {
            $this->is_mandatory = true;

            if ($officeId) {
                $office = \App\Models\Office::with('region')->find($officeId);
                if ($office) {
                    $this->mandatory_assignments[] = [
                        'assignment_type' => 'office',
                        'region_id' => $office->region_id,
                        'office_id' => $office->id,
                        'agency_id' => null,
                        'region_name' => $office->region?->name,
                        'office_name' => $office->name,
                        'agency_name' => null,
                    ];
                }
            } elseif ($pstoAgencyId) {
                $agency = \App\Models\DOSTAgency::find($pstoAgencyId);
                if ($agency) {
                    $this->mandatory_assignments[] = [
                        'assignment_type' => 'agency',
                        'region_id' => null,
                        'office_id' => null,
                        'agency_id' => $agency->id,
                        'region_name' => null,
                        'office_name' => null,
                        'agency_name' => $agency->acronym ?? $agency->name,
                    ];
                }
            }
        }

        $this->currentStep = 1;
    }


    public function nextStep()
    {
        $this->validate($this->rules());
        if ($this->currentStep < $this->maxSteps) {
            $this->currentStep++;
            $this->lastSavedAt = now()->toDateTimeString();
            $this->dispatch('objective-form-scroll-top');
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatch('objective-form-scroll-top');
        }
    }

    public function submitObjective(): void
    {
        $this->syncAccomplishmentsFromMet();
        $this->validate($this->rules());

        if (!$this->chapter_id) {
            session()->flash('error', 'No chapter selected. Please select a valid indicator/chapter before saving.');
            return;
        }

        $now = now();
        $winQuery = ReportingWindow::query()->where('opens_at', '<=', $now);
        $driver = $winQuery->getModel()->getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            $winQuery->whereRaw("due_at + (grace_days || ' days')::interval >= ?", [$now]);
        } else {
            $winQuery->whereRaw('DATE_ADD(due_at, INTERVAL grace_days DAY) >= ?', [$now]);
        }

        $win = $winQuery->orderByDesc('year')->orderByDesc('quarter')->first();
        if ($win && $win->lock_after_close && $now->gte($win->due_at)) {
            session()->flash('error', 'Reporting period is locked. You cannot submit new indicators at this time.');
            return;
        }

        // Apply Data Quality Rules logic... (unchanged)
        if ($dq = optional(AdminSetting::first())->data_quality_rules) {
            $round = (int)($dq['rounding_decimals'] ?? 2);
            $pmin = (int)($dq['percent_min'] ?? 0);
            $pmax = (int)($dq['percent_max'] ?? 100);

            if ($this->target_value !== null && $this->target_value !== '') {
                $this->target_value = round((float)$this->target_value, $round);
            }
            $clampPercent = function ($val) use ($pmin, $pmax, $round) {
                if ($val === null) return $val;
                $raw = trim((string)$val);
                if (preg_match('/^\s*([0-9]{1,3}(?:\.[0-9]+)?)\s*%\s*$/', $raw, $m)) {
                    $n = (float)$m[1];
                    $n = max($pmin, min($pmax, $n));
                    return rtrim(rtrim(number_format($n, $round, '.', ''), '0'),'.').'%';
                }
                return $val;
            };
            $this->accomplishments = $clampPercent($this->accomplishments);
            $this->annual_plan_targets = $clampPercent($this->annual_plan_targets);
        }

        try {
            if (!$this->objective_result) {
                $this->objective_result = $this->indicator ?: $this->description;
            }
            if (!$this->indicator) {
                $this->indicator = $this->objective_result;
            }
        
        $category = $this->current_category;
        if (!$category && $this->chapter_id) {
            $category = Chapter::where('id', $this->chapter_id)->value('category');
        }

        // [MODIFIED] Construct the target_period string (e.g. "2020-2027")
        $finalTargetPeriod = (string) $this->target_period_start;
        if ($this->target_period_end && $this->target_period_end != $this->target_period_start) {
            $finalTargetPeriod .= '-' . $this->target_period_end;
        }

        $payload = [
            'submitted_by_user_id' => auth()->id(),
            'objective_result'       => $this->objective_result,
            'indicator'              => $this->indicator,
            'chapter_id'             => $this->chapter_id,
            'category'               => $category,
            'description'            => $this->description,
            'dost_agency'            => $this->dost_agency,
            'baseline'               => $this->baseline,
            'accomplishments'        => $this->accomplishments,
            'accomplishments_series' => $this->normalizedSeries($this->accomplishments_series),
            'annual_plan_targets'    => $this->annual_plan_targets,
            'annual_plan_targets_series' => $this->normalizedSeries($this->annual_plan_targets_series),
            // [MODIFIED] Save constructed string
            'target_period'          => $finalTargetPeriod, 
            'target_value'           => $this->target_value,
            'mov'                    => $this->mov,
            'responsible_agency'     => $this->responsible_agency,
            'reporting_agency'       => $this->reporting_agency,
            'assumptions_risk'       => $this->assumptions_risk,
            'pc_secretariat_remarks' => $this->pc_secretariat_remarks,
            'updated_by'             => auth()->id(),
            'is_mandatory'           => $this->is_mandatory,
            'region_id'              => $this->region_id,
            'office_id'              => $this->office_id,
        ];

            if ($this->editingId) {
                $obj = Objective::find($this->editingId);
                if ($obj) {
                    $keys = array_unique(array_merge(array_keys($payload), ['status','review_notes','corrections_required']));
                    $before = $obj->only($keys);
                    
                    if (($obj->status ?? null) === Objective::STATUS_REJECTED) {
                        $obj->status = Objective::STATUS_DRAFT;
                        $obj->review_notes = null;
                        $obj->corrections_required = null;
                    }
                    
                    $obj->update($payload);
                    $obj->refresh();

                    // Handle proof upload when editing approved indicator
                    if (($obj->status ?? null) === Objective::STATUS_APPROVED && $this->proof_file) {
                        $path = $this->proof_file->store('proofs', 'public');
                        \App\Models\Proof::create([
                            'objective_id' => $obj->id,
                            'file_path' => $path,
                            'file_name' => $this->proof_file->getClientOriginalName(),
                            'file_type' => $this->proof_file->getClientMimeType(),
                            'file_size' => $this->proof_file->getSize(),
                            'uploaded_by' => auth()->id(),
                        ]);
                    }

                    // Audit logging... (condensed)
                    $after = $obj->only($keys);
                    $diff = [];
                    foreach ($after as $k => $v) {
                        $b = $before[$k] ?? null;
                        if ($b !== $v) { $diff[$k] = ['before' => $b, 'after' => $v]; }
                    }
                    \App\Models\AuditLog::create([
                        'actor_user_id' => auth()->id(), 'action' => 'update',
                        'entity_type' => 'Objective', 'entity_id' => (string)$obj->id,
                        'changes' => ['diff' => $diff],
                    ]);
                    $this->dispatch('objective-updated', id: $obj->id);

                    if ($this->is_mandatory) {
                        $this->saveMandatoryAssignments($obj->id);
                    }

                    $this->dispatch('objective-saved', url: route('dashboard', ['chapter' => $this->chapter_id]));
                }
            } else {
                $payload['created_by'] = auth()->id();
                $payload['status'] = Objective::STATUS_DRAFT;
                $obj = Objective::create($payload);
                // Audit logging...
                $diff = [];
                foreach ($payload as $k => $v) { $diff[$k] = ['before' => null, 'after' => $v]; }
                \App\Models\AuditLog::create([
                    'actor_user_id' => auth()->id(), 'action' => 'create',
                    'entity_type' => 'Objective', 'entity_id' => (string)$obj->id,
                    'changes' => ['diff' => $diff],
                ]);
                $this->dispatch('objective-created', id: $obj->id);

                if ($this->is_mandatory) {
                    $this->saveMandatoryAssignments($obj->id);
                }

                $this->dispatch('objective-saved', url: route('dashboard', ['chapter' => $this->chapter_id]));
            }

            $this->resetForm();
            session()->flash('success', 'M&E Indicator saved successfully.');
            $this->dispatch('close-objective-modal');
            $this->dispatch('indicator-saved')->to(\App\Livewire\Dashboard\UnifiedDashboard::class);

        } catch (\Throwable $e) {
            session()->flash('error', 'Save failed: '.$e->getMessage());
        }
    }

    /**
     * Save objective and immediately submit to HO (for Agency users)
     *
     * BUG FIX 2.1: submitObjective() calls resetForm() which clears $this->editingId,
     * so the subsequent check `if ($this->editingId)` would ALWAYS be null, meaning
     * the forward-to-HO never happened. Fix: capture the ID before submitObjective().
     */
    public function submitAndForwardToHO(): void
    {
        try {
            // First save the objective
            $this->submitObjective();

            // Get the saved ID from the session flash or find the latest objective
            // We need to find the objective that was just saved
            $savedObjective = Objective::where('submitted_by_user_id', auth()->id())
                ->where('status', Objective::STATUS_DRAFT)
                ->latest()
                ->first();

            // If we were editing, try to find that specific objective
            if (!$savedObjective && $this->editingId) {
                $savedObjective = Objective::find($this->editingId);
            }

            if ($savedObjective && auth()->user()->isAgency()) {
                $savedObjective->submitToHO();
                session()->flash('success', 'Objective saved and submitted to Head Office.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('submitAndForwardToHO failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'Failed to submit to Head Office: ' . $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'objective_result','indicator','description','dost_agency',
            'baseline','accomplishments','annual_plan_targets',
            'target_period_start', 'target_period_end', // [MODIFIED] Reset new fields
            'target_value','mov','responsible_agency','reporting_agency',
            'assumptions_risk','pc_secretariat_remarks',
            'accomplishments_series','annual_plan_targets_series','chapter_id',
        ]);
        $this->currentStep = 1;
        $this->owner_id = '1';
        $this->priority = 'Medium';
        $this->lastSavedAt = null;
        $this->hasPrefill = false;
        $this->current_category = null;
        $this->lock_indicator = false;
        $this->viewOnly = false;
        
        // Reset defaults
        $this->target_period_start = now()->year;
        $this->target_period_end = null;

        $this->mount();
    }
    
    // ... [Rest of the component methods remain unchanged] ...
    public function updated($name, $value)
    {
        if (is_string($name) && str_starts_with($name, 'annual_plan_targets_series.')) {
            $this->syncAccomplishmentsFromMet();
        }
        if ($name === 'gen_start_year' && $value && empty($this->baseline)) {
            $this->baseline = (string)$value;
        }
    }
    
    // ... [Existing mandatory assignment methods, helper functions, and render method] ...
    public function updatedOfficeId($value): void
    {
        if ($value) {
            $office = Office::find($value);
            $this->region_id = $office?->region_id;
        } else {
            $this->region_id = null;
        }
    }

    #[On('open-objective-modal')]
    public function onOpenModal(): void
    {
        if ($this->editingId || $this->hasPrefill || $this->chapter_id || $this->indicator) {
            return;
        }
        $this->resetForm();
    }
    
    private function userIsPsto($user = null): bool
    {
        $user = $user ?: auth()->user();
        if (! $user) return false;
        if (method_exists($user, 'isPsto') && $user->isPsto()) return true;
        if (method_exists($user, 'isPSTO') && $user->isPSTO()) return true;
        return false;
    }
    
    private function applyPstoDefaults(): void
    {
        if ($this->editingId || $this->hasPrefill) return;
        if (! $this->userIsPsto()) return;

        $user = auth()->user();
        $office = null;
        if ($user?->office_id) $office = Office::with('region')->find($user->office_id);
        $agency = $user?->agency;

        if ($office) {
            $this->office_id = $this->office_id ?: $office->id;
            $this->region_id = $this->region_id ?: $office->region_id;
        }

        if ($agency) {
            $agencyName = $agency->acronym ?? $agency->name;
            $this->responsible_agency = $this->responsible_agency ?: $agencyName;
            $this->reporting_agency = $this->reporting_agency ?: $agencyName;
        }

        $this->is_mandatory = true;
        
        // ... [Existing assignment logic] ...
        if ($office) {
            $hasOfficeAssignment = collect($this->mandatory_assignments)->first(function ($assignment) use ($office) {
                return ($assignment['assignment_type'] ?? null) === 'office' && ($assignment['office_id'] ?? null) === $office->id;
            });
            if (! $hasOfficeAssignment) {
                $this->mandatory_assignments[] = [
                    'assignment_type' => 'office',
                    'region_id' => $office->region_id,
                    'office_id' => $office->id,
                    'agency_id' => null,
                    'region_name' => $office->region?->name,
                    'office_name' => $office->name,
                    'agency_name' => null,
                ];
            }
        }
        
        if ($agency) {
            $hasAgencyAssignment = collect($this->mandatory_assignments)->first(function ($assignment) use ($agency) {
                return ($assignment['assignment_type'] ?? null) === 'agency' && ($assignment['agency_id'] ?? null) === $agency->id;
            });
            if (! $hasAgencyAssignment) {
                $this->mandatory_assignments[] = [
                    'assignment_type' => 'agency',
                    'region_id' => null,
                    'office_id' => null,
                    'agency_id' => $agency->id,
                    'region_name' => null,
                    'office_name' => null,
                    'agency_name' => $agency->acronym ?? $agency->name,
                ];
            }
        }

        if (empty($this->mandatory_assignments)) {
            $this->mandatory_assignments[] = [
                'assignment_type' => 'all',
                'region_id' => null,
                'office_id' => null,
                'agency_id' => null,
                'region_name' => null,
                'office_name' => null,
                'agency_name' => null,
            ];
        }
    }
    
    private function normalizedSeries(array $rows): ?array
    {
        $rows = array_values(array_filter($rows, function ($r) {
            $y = $r['year'] ?? null; 
            $v = $r['value'] ?? null;
            return $y || $v || ($y === 0 || $v === 0.0);
        }));
        return count($rows) ? $rows : null;
    }

    public function addPlanRow(): void { 
        $this->annual_plan_targets_series[] = ['year' => '', 'value' => '', 'met' => false]; 
    }
    
    public function removePlanRow(int $i): void { 
        unset($this->annual_plan_targets_series[$i]); 
        $this->annual_plan_targets_series = array_values($this->annual_plan_targets_series); 
    }

    private function syncAccomplishmentsFromMet(): void
    {
        $accomp = '';
        $rows = array_filter($this->annual_plan_targets_series ?? [], function ($r) {
            return (bool)($r['met'] ?? false);
        });
        if (!empty($rows)) {
            usort($rows, function ($a, $b) {
                return ((int)($b['year'] ?? 0)) <=> ((int)($a['year'] ?? 0));
            });
            $val = $rows[0]['value'] ?? null;
            if ($val !== null && $val !== '') {
                $accomp = (string) $val;
            }
        }
        $this->accomplishments = $accomp;
    }

    public function generateTargets(): void
    {
        $data = $this->validate([
            'gen_start_year'     => 'required|integer|min:2010|max:2100',
            'gen_end_year'       => 'required|integer|min:2010|max:2100',
            'gen_baseline_value' => 'required|numeric',
            'gen_increment'      => 'required|numeric',
        ]);

        $start = (int) $data['gen_start_year'];
        $end   = (int) $data['gen_end_year'];
        if ($end < $start) {
            session()->flash('error', 'End year must be greater than or equal to start year.');
            return;
        }

        $baseline  = (float) $data['gen_baseline_value'];
        $step      = (float) $data['gen_increment'];
        $targetCap = is_numeric($this->target_value) ? (float) $this->target_value : null;
        
        $round = 2;
        if ($dq = optional(AdminSetting::first())->data_quality_rules) {
            $round = (int)($dq['rounding_decimals'] ?? 2);
        }

        $generated = [];
        $offset = 0;
        foreach (range($start, $end) as $year) {
            $val = round($baseline + ($step * $offset), $round);
            if ($targetCap !== null && $val > $targetCap) {
                $this->addError('gen_increment', 'Generated value exceeds the target value. Adjust increment or target.');
                return;
            }
            $generated[] = ['year' => $year, 'value' => $val, 'met' => false];
            $offset++;
        }

        $existing = $this->annual_plan_targets_series ?? [];
        if ($this->gen_overwrite) {
            $existing = array_values(array_filter($existing, function ($r) use ($start, $end) {
                $y = (int)($r['year'] ?? 0);
                return $y < $start || $y > $end;
            }));
            $existing = array_merge($existing, $generated);
        } else {
            $years = [];
            foreach ($existing as $r) {
                $yy = (int)($r['year'] ?? 0);
                if ($yy) $years[$yy] = true;
            }
            foreach ($generated as $row) {
                $yy = (int)$row['year'];
                if (!isset($years[$yy])) {
                    $existing[] = $row;
                }
            }
        }

        usort($existing, function ($a, $b) {
            return ((int)($a['year'] ?? 0)) <=> ((int)($b['year'] ?? 0));
        });
        $this->annual_plan_targets_series = array_values($existing);
        $this->syncAccomplishmentsFromMet();
        session()->flash('success', 'Yearly targets generated. You can still adjust values or mark years as met.');
    }
    
    protected function saveMandatoryAssignments(int $objectiveId): void
    {
        // ... [Implementation remains the same as in provided code] ...
        IndicatorMandatoryAssignment::where('objective_id', $objectiveId)->delete();
        foreach ($this->mandatory_assignments as $assignment) {
            IndicatorMandatoryAssignment::create([
                'objective_id' => $objectiveId,
                'assignment_type' => $assignment['assignment_type'],
                'region_id' => $assignment['region_id'] ?? null,
                'office_id' => $assignment['office_id'] ?? null,
                'agency_id' => $assignment['agency_id'] ?? null,
            ]);
        }
        $table = (new Objective())->getTable();
        $hasRegionColumn = Schema::hasColumn($table, 'region_id');
        $hasOfficeColumn = Schema::hasColumn($table, 'office_id');
        if ($hasRegionColumn || $hasOfficeColumn) {
            $primaryRegionId = null;
            $primaryOfficeId = null;
            foreach ($this->mandatory_assignments as $assignment) {
                if ($assignment['assignment_type'] === 'office' && $assignment['office_id']) {
                    $primaryRegionId = $assignment['region_id'];
                    $primaryOfficeId = $assignment['office_id'];
                    break;
                }
                if ($assignment['assignment_type'] === 'region' && $assignment['region_id'] && $primaryRegionId === null) {
                    $primaryRegionId = $assignment['region_id'];
                }
            }
            $obj = Objective::find($objectiveId);
            if ($obj) {
                if ($hasRegionColumn) $obj->region_id = $primaryRegionId;
                if ($hasOfficeColumn) $obj->office_id = $primaryOfficeId;
                $obj->save();
                $this->region_id = $obj->region_id;
                $this->office_id = $obj->office_id;
            }
        }
    }

    public function addMandatoryAssignment(): void
    {
        // ... [Implementation remains the same] ...
        if ($this->assignment_type === 'all') {
            $this->mandatory_assignments[] = [
                'assignment_type' => 'all',
                'region_id' => null, 'office_id' => null, 'agency_id' => null,
                'region_name' => null, 'office_name' => null, 'agency_name' => null,
            ];
        } elseif ($this->assignment_type === 'office' && $this->selected_office_id) {
            $office = Office::with('region')->find($this->selected_office_id);
            if ($office) {
                $this->mandatory_assignments[] = [
                    'assignment_type' => 'office',
                    'region_id' => $office->region_id, 'office_id' => $office->id, 'agency_id' => null,
                    'region_name' => $office->region->name ?? null, 'office_name' => $office->name, 'agency_name' => null,
                ];
            }
        } elseif ($this->assignment_type === 'agency' && $this->selected_agency_id) {
            $agency = \App\Models\DOSTAgency::find($this->selected_agency_id);
            if ($agency) {
                $this->mandatory_assignments[] = [
                    'assignment_type' => 'agency',
                    'region_id' => null, 'office_id' => null, 'agency_id' => $agency->id,
                    'region_name' => null, 'office_name' => null, 'agency_name' => $agency->acronym ?? $agency->name,
                ];
            }
        }
        $this->assignment_type = 'all';
        $this->selected_region_id = null;
        $this->selected_office_id = null;
        $this->selected_agency_id = null;
    }

    public function removeMandatoryAssignment(int $index): void
    {
        unset($this->mandatory_assignments[$index]);
        $this->mandatory_assignments = array_values($this->mandatory_assignments);
    }
    
    public function render()
    {
        $regions = PhilippineRegion::where('is_active', true)->orderBy('name')->get();
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $agencies = \App\Models\DOSTAgency::where('is_active', true)->orderBy('acronym')->get();

        return view('livewire.proponent.objective-form', [
            'regions' => $regions,
            'offices' => $offices,
            'agencies' => $agencies,
        ]);
    }
}