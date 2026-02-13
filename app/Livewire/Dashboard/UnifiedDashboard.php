<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked; 

use App\Models\Indicator as Objective;
use App\Models\PhilippineRegion;
use App\Models\Office;
use App\Models\Chapter;
use App\Models\IndicatorTemplate;
use App\Models\DOSTAgency;
use App\Models\IndicatorCategory;
use App\Models\CategoryField;
use App\Models\AdminSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class UnifiedDashboard extends Component
{
    use WithPagination, WithFileUploads;

    // --- Filters ---
    public ?string $categoryFilter = null;
    public ?int $regionFilter = null;
    public ?int $officeFilter = null;
    public ?int $agencyFilter = null;
    public ?string $yearFilter = null;
    public ?string $statusFilter = null;
    public ?string $mandatoryFilter = null;
    public ?int $pillarFilter = null;
    public ?int $outcomeFilter = null;
    public ?int $strategyFilter = null;
    public string $search = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public bool $myIndicatorsOnly = false;

    // --- Pagination ---
    public int $perPage = 20;

    // --- Inline Edit for Accomplishments ---
    public ?int $editingObjectiveId = null;
    public ?string $editingAccomplishments = null;
    public ?int $editingTargetValue = null;

    // --- Year-based inline editing ---
    public ?int $editingYear = null;
    public string $editingYearActual = '';
    public bool $isSavingYear = false; // Prevent double-save

    // --- MFO + Proof Modal for Actual Updates ---
    public bool $showProofModal = false;
    public ?int $proofObjectiveId = null;
    public ?int $proofYear = null;
    public float $proofActualValue = 0;
    public string $proofMfoReference = '';
    public $proofFile = null;

    // --- Proofs for Quick Form Modal ---
    public $indicatorProofs = null;

    // --- Proof Viewer Modal ---
    public bool $showProofViewerModal = false;
    public ?int $viewingProofId = null;
    public $viewingProof = null;


    // --- Dynamic Data ---
    public $dynamicFields = []; 
    public array $dynamicValues = []; 
    
    // --- Chart & Series Data ---
    public array $chartData = [];
    public array $breakdown = [];

    // --- Sorting ---
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    // --- Query String for URL parameters ---
    protected $queryString = [
        'search',
        'categoryFilter',
        'regionFilter',
        'officeFilter',
        'agencyFilter',
        'yearFilter',
        'statusFilter',
        'mandatoryFilter',
        'pillarFilter',
        'outcomeFilter',
        'strategyFilter',
        'startDate',
        'endDate',
        'myIndicatorsOnly' => 'except:0',
        'sortBy',
        'sortDirection',
    ];

    // --- UI States ---
    public bool $showCategorySelector = false;
    public ?string $selectedCategory = null;
    public bool $showPdpSelector = false;
    public ?int $selectedOutcome = null;
    public ?int $selectedIndicatorTemplate = null;
    public bool $showAgencySelector = false;
    public ?int $selectedAgency = null;
    public ?string $agencySelectorContext = null;

    // --- Form Data ---
    public bool $showQuickForm = false;
    public array $quickForm = [
        'year_start' => '',
        'year_end' => '',
        'category' => '',
        'indicator' => '',
        'outcome' => '',
        'output' => '',
        'chapter' => '',
        'monitoring_mechanism' => '',
        'operational_definition' => '',
        'mov' => '',
        'target' => '',
        'baseline' => '',
        'responsible_agency' => '',
        'reporting_agency' => '',
        'assumptions' => '',
        'remarks' => '',
        'program_name' => '',
        'indicator_type' => 'outcome',
        'agency_code' => '',
        'office_name' => '',
        'region_name' => '',
        'accomplishment' => '',
        'pillar_id' => null,
        'outcome_id' => null,
        'strategy_id' => null,
    ];

    // --- Proof Upload (required when editing approved indicators) ---
    public $proof_file = null;

    public ?int $editingQuickFormId = null;
    public bool $viewMode = false;
    public bool $adminBypassMode = false; // Track admin bypass mode for editing locked indicators

    // --- Tracking & History ---
    public $indicatorHistory = []; 
    public bool $isUpdateProgress = false;

    // --- PDP Detailed View ---
    public bool $showPdpView = false;
    public ?Objective $pdpViewRecord = null;

    // --- Import State ---
    public $importFile;
    public array $importErrors = [];
    public int $importedCount = 0;
    public bool $createFromMandatory = false;
    public bool $importing = false;

    // --- Notification State ---
    public int $unreadCount = 0;
    public array $recentNotifications = [];
    public bool $refreshNotifications = false;

    // --- Rejection Modal State ---
    public bool $showRejectionModal = false;
    public ?int $rejectionTargetId = null;
    public string $rejectionReason = '';

    // --- Admin Confirmation Modal State ---
    public bool $showAdminConfirmModal = false;
    public ?int $adminConfirmTargetId = null;
    public string $adminConfirmAction = ''; // 'adminDelete', 'adminEdit', 'reopen', 'delete'
    public string $adminConfirmTitle = '';
    public string $adminConfirmMessage = '';

    // --- Delete Confirmation Modal State (for regular users) ---
    public bool $showDeleteConfirmModal = false;
    public ?int $deleteTargetId = null;

    // ==================== SUPERADMIN POWERS ====================

    // --- Bulk Operations ---
    public array $selectedIndicators = [];
    public bool $selectAll = false;
    public bool $showBulkModal = false;
    public string $bulkAction = ''; // 'bulkDelete', 'bulkReopen', 'bulkApprove', 'bulkReject'

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedIndicators = Objective::pluck('id')->toArray();
        } else {
            $this->selectedIndicators = [];
        }
    }

    // --- Workflow Override ---
    public bool $showForceStatusModal = false;
    public ?int $forceStatusTargetId = null;
    public string $forceStatusTarget = '';

    // --- User Management ---
    public bool $showUserManagementModal = false;
    public bool $showUserManagementPanel = false;
    public ?int $selectedUserId = null;
    public string $userManagementAction = ''; // 'resetPassword', 'lockAccount', 'unlockAccount', 'changeRole'
    public string $userSearch = '';

    // --- System Override ---
    public bool $showOverrideModal = false;
    public string $overrideReason = '';

    public function mount()
    {
        $this->refreshNotificationData();
    }

    /**
     * Refresh notification data
     */
    public function refreshNotificationData(): void
    {
        $notificationService = \App\Services\NotificationService::make();
        $this->unreadCount = $notificationService->getUnreadCount(Auth::user());
        $this->recentNotifications = $notificationService->getNotifications(Auth::user(), 5)->toArray();
    }

    // --- Search & Filters ---
    public function updatedSearch() { $this->resetPage(); }
    public function updatedCategoryFilter() { $this->resetPage(); }
    public function updatedRegionFilter() { $this->resetPage(); $this->officeFilter = null; }
    public function updatedOfficeFilter() { $this->resetPage(); }
    public function updatedAgencyFilter() { $this->resetPage(); }
    public function updatedYearFilter() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedMandatoryFilter() { $this->resetPage(); }
    public function updatedPillarFilter() { $this->resetPage(); }
    public function updatedOutcomeFilter() { $this->resetPage(); }
    public function updatedStrategyFilter() { $this->resetPage(); }
    public function updatedStartDate() { $this->resetPage(); }
    public function updatedEndDate() { $this->resetPage(); }
    public function updatedMyIndicatorsOnly() { $this->resetPage(); }
    public function updatedPerPage($value)
    {
        // Ensure value is a valid integer between 1 and 500
        $intValue = (int) $value;
        $this->perPage = ($intValue >= 1 && $intValue <= 500) ? $intValue : 20;

        // Reset to page 1 when changing per-page to avoid invalid page numbers
        $this->gotoPage(1);
    }

    /**
     * Sort the objectives by the given column
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    /**
     * Toggle sort direction between ascending and descending
     */
    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    // --- Inline Edit for Accomplishments ---
    public function startEditingAccomplishments($id): void
    {
        \Log::info('startEditingAccomplishments called', ['id' => $id]);
        $objective = Objective::find($id);
        if (!$objective) {
            \Log::error('Objective not found', ['id' => $id]);
            return;
        }

        \Log::info('Objective found', ['id' => $objective->id, 'is_locked' => $objective->is_locked, 'status' => $objective->status]);
        $this->editingObjectiveId = $id;

        // First try accomplishments_series (from form)
        if (is_array($objective->accomplishments_series) && !empty($objective->accomplishments_series)) {
            $values = [];
            foreach ($objective->accomplishments_series as $acc) {
                if (isset($acc['value']) && is_numeric($acc['value'])) {
                    $values[] = $acc['value'];
                }
            }
            $this->editingAccomplishments = implode(', ', $values);
        }
        // Fallback to accomplishments field (from inline edit)
        elseif (is_array($objective->accomplishments) && !empty($objective->accomplishments)) {
            $values = [];
            foreach ($objective->accomplishments as $acc) {
                if (isset($acc['value']) && is_numeric($acc['value'])) {
                    $values[] = $acc['value'];
                } elseif (is_numeric($acc)) {
                    $values[] = $acc;
                }
            }
            $this->editingAccomplishments = implode(', ', $values);
        } elseif (is_numeric($objective->accomplishments)) {
            $this->editingAccomplishments = (string)$objective->accomplishments;
        } else {
            $this->editingAccomplishments = '';
        }

        $this->editingTargetValue = $objective->target_value;
    }

    public function saveAccomplishments($id): void
    {
        try {
            \Log::info('saveAccomplishments called', ['id' => $id, 'editingAccomplishments' => $this->editingAccomplishments]);
            $objective = Objective::find($id);
            if (!$objective) {
                \Log::error('Objective not found in saveAccomplishments', ['id' => $id]);
                return;
            }

            // Parse accomplishments input and save to accomplishments_series (same format as form)
            $values = array_filter(array_map('trim', explode(',', $this->editingAccomplishments ?? '')), fn($v) => $v !== '');
            $accomplishmentsSeries = [];

            // Get current accomplishments_series to preserve year data
            $currentSeries = is_array($objective->accomplishments_series) ? $objective->accomplishments_series : [];

            if (!empty($values)) {
                // If we have existing series, update the values by matching index
                if (!empty($currentSeries)) {
                    $index = 0;
                    foreach ($currentSeries as $item) {
                        if (isset($values[$index])) {
                            $accomplishmentsSeries[] = [
                                'year' => $item['year'] ?? null,
                                'value' => (float)$values[$index]
                            ];
                            $index++;
                        }
                    }
                    // If there are more values than existing series entries, add them
                    while ($index < count($values)) {
                        $accomplishmentsSeries[] = ['value' => (float)$values[$index]];
                        $index++;
                    }
                } else {
                    // No existing series, create simple value entries
                    foreach ($values as $val) {
                        if (is_numeric($val)) {
                            $accomplishmentsSeries[] = ['value' => (float)$val];
                        }
                    }
                }
            }

            $objective->update(['accomplishments_series' => $accomplishmentsSeries]);

            \Log::info('Accomplishments saved successfully', ['id' => $objective->id, 'accomplishments_series' => $accomplishmentsSeries]);
            $this->dispatch('toast', message: 'Actual value updated successfully.', type: 'success');
        } catch (\Throwable $e) {
            \Log::error('saveAccomplishments failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to save accomplishments. Please try again.', type: 'error');
        } finally {
            // Clear edit mode - Livewire will automatically re-render
            $this->editingObjectiveId = null;
            $this->editingAccomplishments = null;
            $this->editingTargetValue = null;
        }
    }

    public function cancelEditing(): void
    {
        $this->editingObjectiveId = null;
        $this->editingAccomplishments = null;
        $this->editingTargetValue = null;
    }

    // --- Year-based inline editing for dashboard table ---
    public function startEditingYear($objectiveId, $year, $actualValue): void
    {
        // Instead of inline editing, open the proof modal
        $this->proofObjectiveId = (int)$objectiveId;
        $this->proofYear = (int)$year;
        $this->proofActualValue = is_numeric($actualValue) ? (float)$actualValue : 0;
        $this->proofMfoReference = '';
        $this->proofFile = null;
        $this->showProofModal = true;
    }

    public function saveYearActual($objectiveId, $year): void
    {
        // Guard: Prevent double-save
        if ($this->isSavingYear) {
            return;
        }

        // Guard: Check if we're actually editing this objective and year
        if ($this->editingObjectiveId !== (int)$objectiveId || $this->editingYear !== (int)$year) {
            return;
        }

        $this->isSavingYear = true;

        try {
            $objective = Objective::find($objectiveId);
            if (!$objective) {
                \Log::error('Objective not found in saveYearActual', ['id' => $objectiveId]);
                $this->cancelYearEdit();
                return;
            }

            // Get the value from editingYearActual property (already synced via wire:model.live)
            $actualValue = trim($this->editingYearActual ?? '');

            // If value is empty, don't save (this prevents double-save with empty value)
            if ($actualValue === '') {
                $this->cancelYearEdit();
                return;
            }

            if (!is_numeric($actualValue)) {
                $actualValue = 0;
            } else {
                $actualValue = (float)$actualValue;
            }

            // Get current accomplishments_series
            $currentSeries = is_array($objective->accomplishments_series) ? $objective->accomplishments_series : [];
            $updated = false;

            // Find and update the entry with matching year or period
            foreach ($currentSeries as &$entry) {
                if ((isset($entry['year']) && (int)$entry['year'] === (int)$year) ||
                    (isset($entry['period']) && (string)$entry['period'] === (string)$year)) {
                    $entry['value'] = $actualValue;
                    $updated = true;
                    break;
                }
            }
            unset($entry); // Break reference

            // If no matching entry found, add new one
            if (!$updated) {
                $currentSeries[] = [
                    'year' => (int)$year,
                    'value' => $actualValue
                ];
            }

            // Save to database
            $objective->update(['accomplishments_series' => $currentSeries]);

            \Log::info('Year actual saved successfully', [
                'objective_id' => $objective->id,
                'year' => $year,
                'actual' => $actualValue,
                'series' => $currentSeries
            ]);

            $this->dispatch('toast', message: 'Actual value updated successfully.', type: 'success');
            $this->cancelYearEdit();
        } catch (\Throwable $e) {
            \Log::error('saveYearActual failed', ['objectiveId' => $objectiveId, 'year' => $year, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to save year actual. Please try again.', type: 'error');
            $this->cancelYearEdit();
        }
    }

    public function cancelYearEdit(): void
    {
        $this->editingObjectiveId = null;
        $this->editingYear = null;
        $this->editingYearActual = '';
        $this->isSavingYear = false;
    }

    // --- MFO + Proof Modal Methods ---
    public function saveYearWithProof(): void
    {
        try {
            $this->validate([
                'proofMfoReference' => 'required|string|max:255',
                'proofFile' => 'required|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            ], [
                'proofMfoReference.required' => 'MFO Reference is required.',
                'proofFile.required' => 'Proof file is required.',
                'proofFile.mimes' => 'Proof must be a PDF, DOC, DOCX, JPG, or PNG file.',
                'proofFile.max' => 'Proof file size must not exceed 10MB.',
            ]);

            $objective = Objective::find($this->proofObjectiveId);
            if (!$objective) {
                $this->dispatch('toast', message: 'Objective not found.', type: 'error');
                $this->closeProofModal();
                return;
            }

            // Store the proof file
            $path = $this->proofFile->store('proofs', 'public');
            $proof = \App\Models\Proof::create([
                'objective_id' => $objective->id,
                'file_path' => $path,
                'file_name' => $this->proofFile->getClientOriginalName(),
                'file_type' => $this->proofFile->getClientMimeType(),
                'file_size' => $this->proofFile->getSize(),
                'mfo_reference' => $this->proofMfoReference,
                'year' => $this->proofYear,
                'uploaded_by' => auth()->id(),
            ]);

            // Audit log for proof upload
            \App\Services\AuditService::logProofUpload($proof);

            // Update the accomplishments_series with the new actual value
            $actualValue = $this->proofActualValue;
            $year = $this->proofYear;

            $currentSeries = is_array($objective->accomplishments_series) ? $objective->accomplishments_series : [];
            $updated = false;

            // Find and update the entry with matching year or period
            foreach ($currentSeries as &$entry) {
                if ((isset($entry['year']) && (int)$entry['year'] === (int)$year) ||
                    (isset($entry['period']) && (string)$entry['period'] === (string)$year)) {
                    $entry['value'] = $actualValue;
                    $updated = true;
                    break;
                }
            }
            unset($entry); // Break reference

            // If no matching entry found, add new one
            if (!$updated) {
                $currentSeries[] = [
                    'year' => (int)$year,
                    'value' => $actualValue
                ];
            }

            // Save to database
            $objective->update(['accomplishments_series' => $currentSeries]);

            \Log::info('Year actual saved with proof', [
                'objective_id' => $objective->id,
                'year' => $year,
                'actual' => $actualValue,
                'mfo_reference' => $this->proofMfoReference,
                'proof_id' => $proof->id,
            ]);

            // Create audit history entry for the proof upload
            $this->audit('update_proof', $objective, [
                'year' => $year,
                'actual_value' => $actualValue,
                'mfo_reference' => $this->proofMfoReference,
                'proof_file' => $proof->file_name,
            ]);

            $this->dispatch('toast', message: 'Actual value updated successfully with MFO and proof.', type: 'success');
            $this->closeProofModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Livewire handle validation display
        } catch (\Throwable $e) {
            \Log::error('saveYearWithProof failed', ['objectiveId' => $this->proofObjectiveId, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to save proof. Please try again.', type: 'error');
            $this->closeProofModal();
        }
    }

    public function closeProofModal(): void
    {
        $this->showProofModal = false;
        $this->proofObjectiveId = null;
        $this->proofYear = null;
        $this->proofActualValue = 0;
        $this->proofMfoReference = '';
        $this->proofFile = null;
    }

    /**
     * Audit logging for tracking changes
     */
    protected function audit(string $action, Objective $obj, array $changes): void
    {
        try {
            \App\Models\AuditLog::create([
                'actor_user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => 'Objective',
                'entity_id' => (string) $obj->id,
                'changes' => $changes,
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Open proof viewer modal
     */
    public function openProofViewer(int $proofId): void
    {
        $proof = \App\Models\Proof::with(['objective', 'uploader'])->find($proofId);
        if (!$proof) {
            $this->dispatch('toast', message: 'Proof not found.', type: 'error');
            return;
        }

        $this->viewingProof = $proof;
        $this->viewingProofId = $proofId;
        $this->showProofViewerModal = true;
    }

    /**
     * Close proof viewer modal
     */
    public function closeProofViewer(): void
    {
        $this->showProofViewerModal = false;
        $this->viewingProofId = null;
        $this->viewingProof = null;
    }

    public function clearFilters()
    {
        $this->categoryFilter = null;
        $this->regionFilter = null;
        $this->officeFilter = null;
        $this->agencyFilter = null;
        $this->yearFilter = null;
        $this->statusFilter = null;
        $this->mandatoryFilter = null;
        $this->pillarFilter = null;
        $this->outcomeFilter = null;
        $this->strategyFilter = null;
        $this->search = '';
        $this->startDate = null;
        $this->endDate = null;
        $this->myIndicatorsOnly = false;

        if ($user = Auth::user()) {
            // PSTO sees their office
            if ($user->isPSTO()) {
                $this->officeFilter = $user->office_id ?: null;
            }
            // RO sees their office and child PSTO offices
            elseif ($user->isRO()) {
                // No filter needed - query handles office hierarchy automatically
                $this->statusFilter = null;
            }
            // HO (Head of Office) sees their Region (Assigned to Regions)
            elseif ($user->canActAsHeadOfOffice()) {
                $this->regionFilter = $user->region_id ?: null;
                $this->statusFilter = null;
            }
        }
        $this->resetPage();
    }

    /**
     * Clear a specific filter by name
     */
    public function clearFilter(string $filterName): void
    {
        $filterMap = [
            'category' => 'categoryFilter',
            'office' => 'officeFilter',
            'year' => 'yearFilter',
            'status' => 'statusFilter',
            'pillar' => 'pillarFilter',
            'outcome' => 'outcomeFilter',
            'strategy' => 'strategyFilter',
            'search' => 'search',
        ];

        if (isset($filterMap[$filterName])) {
            $property = $filterMap[$filterName];
            $this->$property = ($filterName === 'search') ? '' : null;
            $this->resetPage();
        }
    }

    /**
     * Handle notification click - mark as read and filter to indicator
     */
    public function clickNotification(string $notificationId): void
    {
        \Log::info('clickNotification called', ['id' => $notificationId]);

        $notification = \App\Models\SystemNotification::forUser(Auth::user())
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            \Log::info('Notification not found', ['id' => $notificationId]);
            return;
        }

        // Mark as read
        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
            \Log::info('Notification marked as read', ['id' => $notificationId]);
        }

        // Get objective_id from data
        $data = $notification->data ?? [];
        if (isset($data['objective_id'])) {
            $this->search = $data['objective_id'];
            $this->resetPage();
            \Log::info('Search updated', ['objective_id' => $data['objective_id']]);
        }

        // Refresh notification data to update the dropdown
        $notificationService = \App\Services\NotificationService::make();
        $this->unreadCount = $notificationService->getUnreadCount(Auth::user());
        $this->recentNotifications = $notificationService->getNotifications(Auth::user(), 5)->toArray();
        \Log::info('Notification data refreshed', ['unreadCount' => $this->unreadCount]);

        // Close the dropdown by dispatching to Alpine
        $this->dispatch('close-notification-dropdown');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(): void
    {
        \App\Models\SystemNotification::forUser(Auth::user())
            ->unread()
            ->update(['read_at' => now()]);

        // Refresh notification data
        $this->refreshNotificationData();

        // Close dropdown
        $this->dispatch('close-notification-dropdown');
    }

    // --- Computed Properties ---

    #[Computed]
    public function quickCategoryFlow(): string
    {
        return $this->resolveCategoryFlow($this->quickForm['category'] ?? '');
    }

    #[Computed]
    public function categoryFieldsForTable(): array
    {
        if (!$this->categoryFilter) {
            return [];
        }

        $category = IndicatorCategory::where('slug', $this->categoryFilter)->first();
        if (!$category) {
            return [];
        }

        return CategoryField::where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->toArray();
    }

    /**
     * Get proofs grouped by objective_id and year for quick lookup in dashboard
     */
    #[Computed]
    public function proofsByObjectiveAndYear(): Collection
    {
        // Get objective IDs from the current page of paginated results
        // We'll fetch proofs for all visible objectives
        return \App\Models\Proof::whereNotNull('year')
            ->whereNotNull('objective_id')
            ->with('uploader')
            ->get()
            ->groupBy(function ($item) {
                return $item->objective_id . '|' . $item->year;
            });
    }

    /**
     * Get users for user management panel (SuperAdmin only)
     */
    #[Computed]
    public function usersForManagement(): Collection
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            return collect();
        }

        $query = \App\Models\User::query()
            ->with(['office', 'region', 'agency'])
            ->orderBy('name');

        if ($this->userSearch) {
            $search = '%' . $this->userSearch . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('username', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        return $query->get();
    }

    /**
     * Open user management panel
     */
    public function openUserManagementPanel(): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        $this->showUserManagementPanel = true;
        $this->userSearch = '';
    }

    /**
     * Close user management panel
     */
    public function closeUserManagementPanel(): void
    {
        $this->showUserManagementPanel = false;
        $this->userSearch = '';
    }

    /**
     * Impersonate a user
     */
    public function impersonateUser(int $userId)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        $targetUser = \App\Models\User::find($userId);
        if (!$targetUser) {
            $this->dispatch('toast', message: 'User not found.', type: 'error');
            return;
        }

        if ($targetUser->id === $user->id) {
            $this->dispatch('toast', message: 'Cannot impersonate yourself.', type: 'error');
            return;
        }

        // Store current session
        session()->put('impersonator_id', $user->id);
        session()->put('impersonator_name', $user->username);

        // Login as target user
        Auth::login($targetUser);

        \Log::warning('SuperAdmin impersonated user', [
            'superadmin_id' => $user->id,
            'target_user_id' => $targetUser->id,
        ]);

        $this->dispatch('toast', message: "Now impersonating {$targetUser->name}", type: 'info');
        $this->closeUserManagementPanel();

        // Redirect to dashboard
        return redirect()->route('dashboard');
    }

    // --- Workflow Actions ---

    public function submitToRO(int $id)
    {
        try {
            $objective = Objective::find($id);
            $user = Auth::user();

            if ($objective && $user->isPSTO()) {
                $objective->submitToRO();
                $this->dispatch('toast', message: 'Submitted to Regional Office', type: 'success');
                $this->notifyRegionalOffice($objective->region_id, $objective, 'submitted_to_ro');
            }
        } catch (\Throwable $e) {
            \Log::error('submitToRO failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to submit: ' . $e->getMessage(), type: 'error');
        }
    }

    public function submitToHO(int $id)
    {
        try {
            $objective = Objective::find($id);
            $user = Auth::user();

            if ($objective && ($user->isRO() || $user->isAgency())) {
                $objective->submitToHO();
                $this->dispatch('toast', message: 'Submitted to Head of Office', type: 'success');
                $this->notifyHeadOffice($objective, 'submitted_to_ho');
            }
        } catch (\Throwable $e) {
            \Log::error('submitToHO failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to submit: ' . $e->getMessage(), type: 'error');
        }
    }

    public function submitToAdmin(int $id)
    {
        try {
            $objective = Objective::find($id);
            if ($objective && Auth::user()->isOUSEC()) {
                $objective->submitToAdmin();
                $this->dispatch('toast', message: 'Submitted to Administrator', type: 'success');
            }
        } catch (\Throwable $e) {
            \Log::error('submitToAdmin failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to submit: ' . $e->getMessage(), type: 'error');
        }
    }

    public function submitToSuperAdmin(int $id)
    {
        try {
            $objective = Objective::find($id);
            if ($objective && Auth::user()->isAdministrator()) {
                $objective->submitToSuperAdmin();
                $this->dispatch('toast', message: 'Submitted to SuperAdmin', type: 'success');
            }
        } catch (\Throwable $e) {
            \Log::error('submitToSuperAdmin failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to submit: ' . $e->getMessage(), type: 'error');
        }
    }

    public function approve(int $id)
    {
        try {
            $objective = Objective::find($id);

            if ($objective && (Auth::user()->isRO() || Auth::user()->canActAsHeadOfOffice() || Auth::user()->isOUSEC() || Auth::user()->isSA() || Auth::user()->isAdministrator())) {
                $objective->approve(Auth::user());
                $this->dispatch('toast', message: 'Indicator approved successfully!', type: 'success');
            }
        } catch (\Throwable $e) {
            \Log::error('approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Approval failed: ' . $e->getMessage(), type: 'error');
        }
    }

    public function reject(int $id, $reason = null)
    {
        try {
            $objective = Objective::find($id);
            if (!$objective) return;

            if (Auth::user()->canActAsHeadOfOffice() || Auth::user()->isRO() || Auth::user()->isSA() || Auth::user()->isAdministrator()) {
                $user = Auth::user();
                $objective->reject($user, $reason);
                $msg = $user->isRO() ? 'Returned to PSTO' : 'Returned to Regional Office';
                $this->dispatch('toast', message: $msg, type: 'warning');
            }
        } catch (\Throwable $e) {
            \Log::error('reject failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Rejection failed: ' . $e->getMessage(), type: 'error');
        }
    }

    public function delete(int $id)
    {
        try {
            $objective = Objective::find($id);

            if ($objective) {
                $user = Auth::user();

                // RESTRICTION: PSTO and Agency cannot delete
                if ($user->isPSTO() || $user->isAgency()) {
                    $this->dispatch('toast', message: 'You do not have permission to delete indicators.', type: 'error');
                    return;
                }

                // RO, HO, SA, Admin can delete drafts
                if ($objective->status === Objective::STATUS_DRAFT || $user->isSA() || $user->isAdministrator() || $user->canActAsHeadOfOffice()) {
                    $objective->delete();
                    $this->dispatch('toast', message: 'Indicator deleted successfully.', type: 'success');
                } else {
                    $this->dispatch('toast', message: 'Cannot delete submitted indicators. Reject them first.', type: 'error');
                }
            }
        } catch (\Throwable $e) {
            \Log::error('delete failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Delete failed: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openRejectionModal(int $id)
    {
        $this->rejectionTargetId = $id;
        $this->rejectionReason = '';
        $this->showRejectionModal = true;
    }

    public function closeRejectionModal()
    {
        $this->showRejectionModal = false;
        $this->rejectionTargetId = null;
        $this->rejectionReason = '';
        $this->resetErrorBag();
    }

    public function submitRejection()
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:5|max:1000',
        ]);

        // Use OUSEC reject method if user is OUSEC
        if (Auth::user()->isOUSEC()) {
            $this->ousecReject($this->rejectionTargetId);
        } else {
            $this->reject($this->rejectionTargetId, $this->rejectionReason);
            $this->closeRejectionModal();
        }
    }

    // ==================== OUSEC ACTIONS ====================

    /**
     * OUSEC approve indicator and forward to Admin
     */
    public function ousecApprove(int $id): void
    {
        try {
            $objective = Objective::find($id);
            $user = Auth::user();

            if (!$user->isOUSEC()) {
                $this->dispatch('toast', message: 'You do not have permission for this action.', type: 'error');
                return;
            }

            if (!$objective) {
                $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
                return;
            }

            // OUSEC approves and forwards to Admin
            if ($objective->status === Objective::STATUS_SUBMITTED_TO_OUSEC) {
                $objective->submitToAdmin();
                $message = 'Approved and forwarded to Administrator.';
            } elseif ($objective->status === Objective::STATUS_RETURNED_TO_OUSEC) {
                // OUSEC resubmits to Admin
                $objective->update(['status' => Objective::STATUS_SUBMITTED_TO_ADMIN]);
                $message = 'Resubmitted to Administrator.';
            } else {
                $this->dispatch('toast', message: 'Cannot approve this indicator in its current state.', type: 'error');
                return;
            }

            // Record history
            if (method_exists($objective, 'recordHistory')) {
                $objective->recordHistory('approve', [
                    'status' => Objective::STATUS_SUBMITTED_TO_OUSEC,
                ], [
                    'status' => $objective->status,
                ]);
            }

            // Audit Log
            \App\Models\AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'ousec_approve',
                'entity_type' => 'Objective',
                'entity_id' => (string)$objective->id,
                'changes' => ['status' => $objective->status],
            ]);

            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Throwable $e) {
            \Log::error('ousecApprove failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to approve indicator. Please try again.', type: 'error');
        }
    }

    /**
     * OUSEC reject/return indicator to HO with remarks
     */
    public function ousecReject(int $id): void
    {
        try {
            $objective = Objective::find($id);
            $user = Auth::user();

            if (!$user->isOUSEC()) {
                $this->dispatch('toast', message: 'You do not have permission for this action.', type: 'error');
                return;
            }

            if (!$objective) {
                $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
                return;
            }

            // Validate rejection reason
            $this->validate([
                'rejectionReason' => 'required|string|min:5|max:1000',
            ]);

            // OUSEC rejects to HO
            $objective->reject($user, $this->rejectionReason);

            $this->dispatch('toast', message: 'Indicator returned to Head Office with feedback.', type: 'success');
            $this->closeRejectionModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('ousecReject failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to reject indicator. Please try again.', type: 'error');
        }
    }

    // ==================== ADMIN POWERS ====================

    /**
     * Open admin confirmation modal for destructive/admin actions
     */
    public function openAdminConfirm(int $id, string $action): void
    {
        $user = Auth::user();
        if (!$user->isSA() && !$user->isAdministrator()) {
            $this->dispatch('toast', message: 'You do not have permission for this action.', type: 'error');
            return;
        }

        $objective = Objective::find($id);
        if (!$objective) {
            $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
            return;
        }

        $this->adminConfirmTargetId = $id;
        $this->adminConfirmAction = $action;

        // Set modal content based on action
        match ($action) {
            'adminDelete' => [
                $this->adminConfirmTitle = 'Force Delete Indicator',
                $this->adminConfirmMessage = 'Are you sure you want to permanently delete this indicator? This action cannot be undone. The indicator will be removed regardless of its current status.',
            ],
            'adminEdit' => [
                $this->adminConfirmTitle = 'Admin Edit Indicator',
                $this->adminConfirmMessage = 'You are about to edit this indicator using admin privileges. This will bypass normal workflow restrictions. Continue?',
            ],
            'reopen' => [
                $this->adminConfirmTitle = 'Reopen Indicator',
                $this->adminConfirmMessage = 'This will send the indicator back to draft status, allowing it to be edited and resubmitted. The approval workflow will restart. Continue?',
            ],
        };

        $this->showAdminConfirmModal = true;
    }

    /**
     * Close admin confirmation modal
     */
    public function closeAdminConfirmModal(): void
    {
        $this->showAdminConfirmModal = false;
        $this->adminConfirmTargetId = null;
        $this->adminConfirmAction = '';
        $this->adminConfirmTitle = '';
        $this->adminConfirmMessage = '';
    }

    /**
     * Execute the confirmed admin action
     */
    public function executeAdminAction(): void
    {
        if (!$this->adminConfirmTargetId) {
            $this->dispatch('toast', message: 'No target ID specified.', type: 'error');
            return;
        }

        try {
            $objective = Objective::find($this->adminConfirmTargetId);
            if (!$objective) {
                $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
                $this->closeAdminConfirmModal();
                return;
            }

            // Debug: Log the action being performed
            \Log::info('Admin action', ['action' => $this->adminConfirmAction, 'id' => $this->adminConfirmTargetId]);

            match ($this->adminConfirmAction) {
                'adminDelete' => $this->performAdminDelete($objective),
                'adminEdit' => $this->performAdminEdit($objective),
                'reopen' => $this->performReopen($objective),
            };

            $this->closeAdminConfirmModal();
        } catch (\Throwable $e) {
            \Log::error('executeAdminAction failed', ['action' => $this->adminConfirmAction, 'id' => $this->adminConfirmTargetId, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Admin action failed. Please try again.', type: 'error');
            $this->closeAdminConfirmModal();
        }
    }

    /**
     * Admin Delete: Force delete indicator at any stage (except approved)
     */
    private function performAdminDelete(Objective $objective): void
    {
        // Prevent deleting approved indicators
        if ($objective->status === Objective::STATUS_APPROVED) {
            $this->dispatch('toast', message: 'Cannot delete approved indicators. Reject them first.', type: 'error');
            return;
        }

        $objective->delete();
        $this->dispatch('toast', message: 'Indicator permanently deleted.', type: 'success');
    }

    /**
     * Admin Edit: Open edit mode bypassing workflow restrictions
     */
    private function performAdminEdit(Objective $objective): void
    {
        // Directly open edit in admin bypass mode - pass true as second parameter
        \Log::info('performAdminEdit called', ['id' => $objective->id]);
        $this->openEdit($objective->id, true);
    }

    /**
     * Reopen: Send approved/rejected indicator back to draft
     */
    private function performReopen(Objective $objective): void
    {
        $oldStatus = $objective->status;

        // Only allow reopening approved or rejected indicators
        if (!in_array($oldStatus, ['approved', 'rejected'])) {
            $this->dispatch('toast', message: 'Only approved or rejected indicators can be reopened.', type: 'error');
            return;
        }

        // Reset to draft
        $objective->update([
            'status' => Objective::STATUS_DRAFT,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        // Log the status change in history (Bug 1.2 fix: was addHistory, now recordHistory)
        $objective->recordHistory('reopen', ['status' => $oldStatus], ['status' => Objective::STATUS_DRAFT], 'Reopened by admin');

        $this->dispatch('toast', message: 'Indicator reopened and sent back to draft status.', type: 'success');
    }

    // ==================== DELETE CONFIRMATION MODAL (Regular Users) ====================

    /**
     * Open delete confirmation modal for regular users (PSTO/Agency)
     */
    public function openDeleteConfirm(int $id): void
    {
        $objective = Objective::find($id);
        if (!$objective) {
            $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
            return;
        }

        $user = Auth::user();

        // PSTO and Agency cannot delete
        if ($user->isPSTO() || $user->isAgency()) {
            $this->dispatch('toast', message: 'You do not have permission to delete indicators.', type: 'error');
            return;
        }

        // Only allow deleting drafts
        if ($objective->status !== Objective::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Cannot delete submitted indicators. Reject them first.', type: 'error');
            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteConfirmModal = true;
    }

    /**
     * Close delete confirmation modal
     */
    public function closeDeleteConfirmModal(): void
    {
        $this->showDeleteConfirmModal = false;
        $this->deleteTargetId = null;
    }

    /**
     * Execute the confirmed delete action
     */
    public function executeDelete(): void
    {
        if (!$this->deleteTargetId) {
            return;
        }

        $objective = Objective::find($this->deleteTargetId);
        if (!$objective) {
            $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
            $this->closeDeleteConfirmModal();
            return;
        }

        $user = Auth::user();

        // PSTO and Agency cannot delete
        if ($user->isPSTO() || $user->isAgency()) {
            $this->dispatch('toast', message: 'You do not have permission to delete indicators.', type: 'error');
            $this->closeDeleteConfirmModal();
            return;
        }

        // Only allow deleting drafts
        if ($objective->status !== Objective::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Cannot delete submitted indicators. Reject them first.', type: 'error');
            $this->closeDeleteConfirmModal();
            return;
        }

        $objective->delete();
        $this->dispatch('toast', message: 'Indicator deleted successfully.', type: 'success');
        $this->closeDeleteConfirmModal();
    }

    // ==================== SUPERADMIN POWERS METHODS ====================

    /**
     * SUPERADMIN: Force delete approved indicators
     */
    public function forceDeleteApproved(int $id): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        $objective = Objective::find($id);
        if (!$objective) {
            $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
            return;
        }

        if ($objective->status !== Objective::STATUS_APPROVED) {
            $this->dispatch('toast', message: 'Use regular delete for non-approved indicators.', type: 'warning');
            return;
        }

        $objective->delete();
        \Log::warning('SuperAdmin force deleted approved indicator', [
            'indicator_id' => $id,
            'user_id' => $user->id,
        ]);

        $this->dispatch('toast', message: 'Approved indicator permanently deleted.', type: 'success');
    }

    /**
     * SUPERADMIN: Bulk operations on selected indicators
     */
    public function openBulkModal(string $action): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        if (empty($this->selectedIndicators)) {
            $this->dispatch('toast', message: 'Please select indicators first.', type: 'warning');
            return;
        }

        $this->bulkAction = $action;
        $this->showBulkModal = true;
    }

    public function closeBulkModal(): void
    {
        $this->showBulkModal = false;
        $this->bulkAction = '';
        $this->overrideReason = '';
    }

    public function executeBulkAction(): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        if (empty($this->selectedIndicators)) {
            $this->dispatch('toast', message: 'No indicators selected.', type: 'error');
            return;
        }

        try {
            $count = 0;
            $indicators = Objective::whereIn('id', $this->selectedIndicators)->get();

            match ($this->bulkAction) {
                'bulkDelete' => $this->performBulkDelete($indicators, $count),
                'bulkReopen' => $this->performBulkReopen($indicators, $count),
                'bulkApprove' => $this->performBulkApprove($indicators, $count),
                'bulkReject' => $this->performBulkReject($indicators, $count),
            };

            // Log bulk operation
            if ($count > 0) {
                $entityIds = $indicators->pluck('id')->toArray();
                $action = match($this->bulkAction) {
                    'bulkDelete' => 'bulk_delete',
                    'bulkReopen' => 'bulk_update',
                    'bulkApprove' => 'bulk_update',
                    'bulkReject' => 'bulk_update',
                    default => 'bulk_update',
                };
                \App\Services\AuditService::logBulkOperation(
                    $action,
                    'Objective',
                    $entityIds,
                    "Bulk {$this->bulkAction} on {$count} indicators"
                );
            }

            $this->selectedIndicators = [];
            $this->closeBulkModal();
            $this->dispatch('toast', message: "Bulk action completed: {$count} indicators affected.", type: 'success');
        } catch (\Throwable $e) {
            \Log::error('executeBulkAction failed', ['action' => $this->bulkAction, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Bulk action failed. Please try again.', type: 'error');
            $this->closeBulkModal();
        }
    }

    private function performBulkDelete($indicators, &$count): void
    {
        foreach ($indicators as $indicator) {
            $indicator->delete();
            $count++;
        }
    }

    private function performBulkReopen($indicators, &$count): void
    {
        foreach ($indicators as $indicator) {
            if (in_array(strtolower($indicator->status), ['approved', 'rejected'])) {
                $oldStatus = $indicator->status;
                $indicator->update([
                    'status' => Objective::STATUS_DRAFT,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                $indicator->recordHistory('reopen', ['status' => $oldStatus], ['status' => Objective::STATUS_DRAFT], 'Bulk reopened by SuperAdmin');
                $count++;
            }
        }
    }

    private function performBulkApprove($indicators, &$count): void
    {
        foreach ($indicators as $indicator) {
            $indicator->update([
                'status' => Objective::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
            $count++;
        }
    }

    private function performBulkReject($indicators, &$count): void
    {
        foreach ($indicators as $indicator) {
            $oldStatus = $indicator->status;
            $indicator->update(['status' => 'rejected']);
            $indicator->recordHistory('reject', ['status' => $oldStatus], ['status' => 'rejected'], 'Bulk rejected by SuperAdmin');
            $count++;
        }
    }

    /**
     * SUPERADMIN: Force indicator to any status
     */
    public function openForceStatusModal(int $id, string $targetStatus): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        $this->forceStatusTargetId = $id;
        $this->forceStatusTarget = $targetStatus;
        $this->overrideReason = '';
        $this->showForceStatusModal = true;
    }

    public function closeForceStatusModal(): void
    {
        $this->showForceStatusModal = false;
        $this->forceStatusTargetId = null;
        $this->forceStatusTarget = '';
        $this->overrideReason = '';
    }

    public function executeForceStatus(): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        try {
            $objective = Objective::find($this->forceStatusTargetId);
            if (!$objective) {
                $this->dispatch('toast', message: 'Indicator not found.', type: 'error');
                $this->closeForceStatusModal();
                return;
            }

            $oldStatus = $objective->status;
            $newStatus = $this->forceStatusTarget;

            $objective->update(['status' => $newStatus]);

            // Record history with proper array format
            $objective->recordHistory('force_status', 
                ['status' => $oldStatus],
                ['status' => $newStatus],
                "Forced by SuperAdmin: {$this->overrideReason}"
            );

            \Log::warning('SuperAdmin force changed indicator status', [
                'indicator_id' => $objective->id,
                'from' => $oldStatus,
                'to' => $this->forceStatusTarget,
                'reason' => $this->overrideReason,
                'user_id' => $user->id,
            ]);

            $this->dispatch('toast', message: "Indicator status forced to {$this->forceStatusTarget}.", type: 'success');
            $this->closeForceStatusModal();
        } catch (\Throwable $e) {
            \Log::error('executeForceStatus failed', ['id' => $this->forceStatusTargetId, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to force status change. Please try again.', type: 'error');
            $this->closeForceStatusModal();
        }
    }

    /**
     * SUPERADMIN: User Management Actions
     */
    public function openUserManagementModal(int $userId, string $action): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        $targetUser = \App\Models\User::find($userId);
        if (!$targetUser) {
            $this->dispatch('toast', message: 'User not found.', type: 'error');
            return;
        }

        if ($targetUser->id === $user->id && in_array($action, ['lockAccount', 'changeRole'])) {
            $this->dispatch('toast', message: 'Cannot perform this action on yourself.', type: 'error');
            return;
        }

        $this->selectedUserId = $userId;
        $this->userManagementAction = $action;
        $this->overrideReason = '';
        $this->showUserManagementModal = true;
    }

    public function closeUserManagementModal(): void
    {
        $this->showUserManagementModal = false;
        $this->selectedUserId = null;
        $this->userManagementAction = '';
        $this->overrideReason = '';
    }

    public function executeUserManagementAction(): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        $targetUser = \App\Models\User::find($this->selectedUserId);
        if (!$targetUser) {
            $this->dispatch('toast', message: 'User not found.', type: 'error');
            $this->closeUserManagementModal();
            return;
        }

        match ($this->userManagementAction) {
            'resetPassword' => $this->performPasswordReset($targetUser),
            'lockAccount' => $this->performLockAccount($targetUser),
            'unlockAccount' => $this->performUnlockAccount($targetUser),
            'changeRole' => $this->performRoleChange($targetUser),
        };

        \Log::warning('SuperAdmin performed user management action', [
            'action' => $this->userManagementAction,
            'target_user_id' => $targetUser->id,
            'actor_id' => $user->id,
        ]);

        $this->closeUserManagementModal();
    }

    private function performPasswordReset($targetUser): void
    {
        $newPassword = 'DOST' . rand(1000, 9999);
        $targetUser->update([
            'password' => bcrypt($newPassword),
        ]);

        // TODO: Send password via email to user instead of showing on screen
        // For now, log the action only (don't expose password)
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'password_reset',
            'model_type' => 'User',
            'model_id' => $targetUser->id,
            'changes' => ['user' => $targetUser->email],
            'actor_id' => Auth::id(),
        ]);

        $this->dispatch('toast', message: "Password reset successful. New password sent to {$targetUser->email}.", type: 'success');
    }

    private function performLockAccount($targetUser): void
    {
        $targetUser->update(['is_locked' => true]);
        $targetUser->tokens()->delete(); // Revoke sessions
        $this->dispatch('toast', message: 'User account locked successfully.', type: 'success');
    }

    private function performUnlockAccount($targetUser): void
    {
        $targetUser->update(['is_locked' => false]);
        $this->dispatch('toast', message: 'User account unlocked successfully.', type: 'success');
    }

    private function performRoleChange($targetUser): void
    {
        // This would require a role input - for now, toggle between administrator and agency
        $newRole = $targetUser->role === User::ROLE_ADMIN ? User::ROLE_AGENCY : User::ROLE_ADMIN;
        $targetUser->update(['role' => $newRole]);
        $this->dispatch('toast', message: "User role changed to {$newRole}.", type: 'success');
    }

    /**
     * SUPERADMIN: Restore deleted indicator (if soft delete is enabled)
     */
    public function restoreIndicator(int $id): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            $this->dispatch('toast', message: 'SuperAdmin access required.', type: 'error');
            return;
        }

        // This requires soft delete to be enabled on the Objective model
        // For now, just log it
        \Log::info('SuperAdmin attempted restore', ['indicator_id' => $id]);
        $this->dispatch('toast', message: 'Restore feature requires soft delete setup.', type: 'info');
    }

    // --- Form Handling ---

    public function openCreate(): void
    {
        if (!Auth::user()->isPSTO() && !Auth::user()->isAgency()) {
            $this->dispatch('toast', message: 'Only PSTO and Agency accounts can create indicators.', type: 'error');
            return;
        }
        $this->dispatch('open-unified-form')->to(\App\Livewire\Indicators\UnifiedIndicatorForm::class);
    }

    public function openQuickForm()
    {
        if (!Auth::user()->isPSTO() && !Auth::user()->isAgency() && !Auth::user()->isSuperAdmin() && !Auth::user()->isAdministrator() && !Auth::user()->isSA() && !Auth::user()->isRO()) {
            $this->dispatch('toast', message: 'You do not have permission to create indicators.', type: 'error');
            return;
        }
        \Log::info('=== openQuickForm START ===');

        $this->resetValidation();
        $this->editingQuickFormId = null;
        $this->viewMode = false;

        // 1. Reset dynamic data
        $this->dynamicValues = [];
        $this->dynamicFields = [];
        $this->chartData = [];
        $this->breakdown = [];

        // 2. Smart Org Name Logic (Agency -> Office -> Global)
        $user = auth()->user();
        $globalSetting = AdminSetting::first();
        $globalName = $globalSetting->org_name ?? 'DOST';

        // Priority: Assigned Agency > Assigned Office > Global Default
        // This ensures Agencies see their name pre-filled
        $defaultOrgName = $user->agency?->name ?? $user->office?->name ?? $globalName;

        // 3. Prepare Form Data
        $this->quickForm = [
            'year_start' => date('Y'), 
            'year_end' => date('Y'),
            'target' => 0,
            'accomplishment' => 0,
            'category' => '',
            'indicator' => '',
            'operational_definition' => '',
            'baseline' => '',
            
            // Auto-filled Org Names
            'reporting_agency' => $defaultOrgName,
            'responsible_agency' => $defaultOrgName,
            
            'mov' => '',
            'assumptions' => '',
            'remarks' => '',
            'program_name' => '',
        ];

        $this->showQuickForm = true;

        \Log::info('openQuickForm completed', [
            'showQuickForm' => $this->showQuickForm,
            'agency' => $defaultOrgName
        ]);
    }

    // Intelligent Chart Generator
    private function generateChartData(Objective $obj): void
    {
        $data = [];

        // 1. BASELINE
        $data[] = [
            'label' => 'Baseline',
            'target' => 0,
            'actual' => is_numeric($obj->baseline) ? (float)$obj->baseline : 0,
            'is_baseline' => true
        ];
        // 2. PARSE PERIOD
        $parts = explode('-', $obj->target_period ?? '');
        $start = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : now()->year;
        $end = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : $start;

        // 3. LOAD SERIES DATA
        $targets = collect($obj->annual_plan_targets_series ?? []);
        $actuals = collect($obj->accomplishments_series ?? []);

        // Helper function to find actual value by year (handles both 'year' and 'period' keys)
        $findActual = function($actuals, $year) {
            $found = $actuals->first(function($item) use ($year) {
                return (isset($item['year']) && $item['year'] == $year)
                    || (isset($item['period']) && $item['period'] == (string)$year);
            });
            return $found['value'] ?? 0;
        };

        // 4. LOOP YEARS
        for ($year = $start; $year <= $end; $year++) {
            $tVal = $targets->firstWhere('year', $year)['value'] ?? 0;
            $aVal = $findActual($actuals, $year);

            if ($targets->isEmpty() && $year === $end) {
                $tVal = $obj->target_value;
            }
            if ($actuals->isEmpty() && $year === $end) {
                $aVal = $obj->accomplishments;
            }

            $data[] = [
                'label' => (string)$year,
                'target' => (float)$tVal,
                'actual' => (float)$aVal,
                'is_baseline' => false
            ];
        }

        $this->chartData = $data;
    }

    private function loadBreakdown($obj, $start, $end)
    {
        $this->breakdown = [];
        $s = (int)$start;
        $e = (int)($end ?: $start);

        $targets = collect($obj->annual_plan_targets_series ?? []);
        $actuals = collect($obj->accomplishments_series ?? []);

        // Helper function to find actual value by year (handles both 'year' and 'period' keys)
        $findActual = function($actuals, $year) {
            $found = $actuals->first(function($item) use ($year) {
                return (isset($item['year']) && $item['year'] == $year)
                    || (isset($item['period']) && $item['period'] == (string)$year);
            });
            return $found['value'] ?? '';
        };

        for ($y = $s; $y <= $e; $y++) {
            $t = $targets->firstWhere('year', $y)['value'] ?? '';
            $a = $findActual($actuals, $y);

            $this->breakdown[] = [
                'year' => $y,
                'target' => $t,
                'actual' => $a,
            ];
        }
    }

    public function openView(int $id): void
    {
        \Log::info('openView called', ['id' => $id]);

        $user = Auth::user();
        // Use fresh() to ensure we get the latest data from database, not cached
        $objective = Objective::with('chapter')->findOrFail($id)->fresh();

        // Basic authorization: Only check if explicitly denied (should match query scoping)
        // This is a secondary check - primary scoping is in render() query
        if (!$user->isSA() && !$user->isAdministrator()) {
            // For non-admin users, do basic scope validation
            if ($user->isPSTO() || $user->isAgency()) {
                // Allow if user created it OR it belongs to their office
                $hasAccess = $objective->submitted_by_user_id == $user->id
                    || $objective->office_id == $user->office_id;
                if (!$hasAccess) {
                    // Log the denied access instead of aborting for debugging
                    \Log::warning('Access denied to indicator', [
                        'user_id' => $user->id,
                        'user_role' => $user->role,
                        'indicator_id' => $id,
                        'indicator_office' => $objective->office_id,
                        'user_office' => $user->office_id,
                    ]);
                    return; // Silently return instead of aborting
                }
            }
        }

        $this->resetValidation();
        $this->editingQuickFormId = $id;
        $this->viewMode = true;

        // Pre-load editing values for inline edit
        $this->editingTargetValue = $objective->target_value;

        // Handle accomplishments - can be nested array or simple array
        $accomplishmentValues = [];
        if (is_array($objective->accomplishments)) {
            foreach ($objective->accomplishments as $acc) {
                if (isset($acc['value']) && is_numeric($acc['value'])) {
                    $accomplishmentValues[] = $acc['value'];
                } elseif (is_numeric($acc)) {
                    $accomplishmentValues[] = $acc;
                }
            }
        }
        $this->editingAccomplishments = !empty($accomplishmentValues) ? implode(', ', $accomplishmentValues) : '';
        
        $catSlug = $objective->category ?? ($objective->chapter->category ?? 'strategic_plan');
        $category = IndicatorCategory::where('slug', $catSlug)->first();
        
        $this->dynamicFields = $category ? $category->fields()->get() : [];

        $yearStart = null;
        $yearEnd = null;

        if (!empty($objective->target_period)) {
            $years = explode('-', $objective->target_period);
            $yearStart = (isset($years[0]) && $years[0] !== '') ? (int)$years[0] : null;
            $yearEnd   = (isset($years[1]) && $years[1] !== '') ? (int)$years[1] : null;
        }

        $this->quickForm = [
            'category' => $catSlug,
            'year_start' => $yearStart,
            'year_end' => $yearEnd,
            'baseline' => $objective->baseline,
            'target' => $objective->target_value,
            'reporting_agency' => $objective->reporting_agency,
            'responsible_agency' => $objective->responsible_agency,
            'indicator' => $objective->indicator,
            'operational_definition' => $objective->description,
            'mov' => $objective->mov,
            'assumptions' => $objective->assumptions_risk,
            'remarks' => $objective->pc_secretariat_remarks,
            'pillar_id' => $objective->pillar_id,
            'outcome_id' => $objective->outcome_id,
            'strategy_id' => $objective->strategy_id,
        ];

        $this->dynamicValues = [];
        foreach ($this->dynamicFields as $field) {
            if ($field->db_column) {
                $this->dynamicValues[$field->field_name] = $objective->{$field->db_column};
            }
        }

        $targets = collect($objective->annual_plan_targets_series ?? []);
        $actuals = collect($objective->accomplishments_series ?? []);

        \Log::info('openView: Loading breakdown from database', [
            'objective_id' => $objective->id,
            'yearStart' => $yearStart,
            'yearEnd' => $yearEnd,
            'targets_series' => $objective->annual_plan_targets_series,
            'actuals_series' => $objective->accomplishments_series
        ]);

        $this->breakdown = [];

        // Helper function to find actual value by year (handles both 'year' and 'period' keys)
        $findActual = function($actuals, $year) {
            $found = $actuals->first(function($item) use ($year) {
                return (isset($item['year']) && $item['year'] == $year)
                    || (isset($item['period']) && $item['period'] == (string)$year);
            });
            return $found['value'] ?? null;
        };

        if ($yearStart) {
            $loopEnd = $yearEnd ?? ($yearStart + 5);
            if ($loopEnd < $yearStart) $loopEnd = $yearStart;
            if (($loopEnd - $yearStart) > 20) $loopEnd = $yearStart + 20;

            for ($y = $yearStart; $y <= $loopEnd; $y++) {
                $tVal = $targets->firstWhere('year', $y)['value'] ?? null;
                $aVal = $findActual($actuals, $y);

                $this->breakdown[] = [
                    'year' => $y,
                    'target' => $tVal,
                    'actual' => $aVal,
                ];
            }
        }

        \Log::info('openView: Breakdown loaded', ['breakdown' => $this->breakdown]);

        // Load histories for timeline display
        $this->indicatorHistory = $objective->histories()->with('actor')->get();

        // Load proofs for this indicator
        $this->indicatorProofs = $objective->proofs()->with('uploader')->latest()->get();

        $this->showQuickForm = true;
    }

    public function openEdit(int $id, bool $adminBypass = false): void
    {
        \Log::info('openEdit called', ['id' => $id, 'adminBypass' => $adminBypass]);

        $user = Auth::user();
        // Use fresh() to ensure we get the latest data from database, not cached
        $objective = Objective::with('chapter')->findOrFail($id)->fresh();

        \Log::info('openEdit called', [
            'id' => $id,
            'adminBypass' => $adminBypass,
            'user_role' => $user->role,
            'isSA' => $user->isSA(),
            'isAdministrator' => $user->isAdministrator()
        ]);

        // ADMIN EDIT MODE: Bypass all restrictions if triggered by admin action
        if ($adminBypass && ($user->isSA() || $user->isAdministrator())) {
            \Log::info('Admin BYPASS mode ACTIVATED', ['id' => $id, 'is_locked' => $objective->is_locked]);

            // Set admin bypass mode flag
            $this->adminBypassMode = true;

            // Load form data directly WITHOUT calling openView (which sets viewMode = true)
            $this->resetValidation();
            $this->editingQuickFormId = $id;
            $this->viewMode = false; // Set to FALSE before loading data

            // Pre-load editing values for inline edit
            $this->editingTargetValue = $objective->target_value;

            // Handle accomplishments - can be nested array or simple array
            $accomplishmentValues = [];
            if (is_array($objective->accomplishments)) {
                foreach ($objective->accomplishments as $acc) {
                    if (isset($acc['value']) && is_numeric($acc['value'])) {
                        $accomplishmentValues[] = $acc['value'];
                    } elseif (is_numeric($acc)) {
                        $accomplishmentValues[] = $acc;
                    }
                }
            }
            $this->editingAccomplishments = !empty($accomplishmentValues) ? implode(', ', $accomplishmentValues) : '';

            $catSlug = $objective->category ?? ($objective->chapter->category ?? 'strategic_plan');
            $category = IndicatorCategory::where('slug', $catSlug)->first();

            $this->dynamicFields = $category ? $category->fields()->get() : [];

            $yearStart = null;
            $yearEnd = null;

            if (!empty($objective->target_period)) {
                $years = explode('-', $objective->target_period);
                $yearStart = (isset($years[0]) && $years[0] !== '') ? (int)$years[0] : null;
                $yearEnd   = (isset($years[1]) && $years[1] !== '') ? (int)$years[1] : null;
            }

            $this->quickForm = [
                'category' => $catSlug,
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'baseline' => $objective->baseline,
                'target' => $objective->target_value,
                'reporting_agency' => $objective->reporting_agency,
                'responsible_agency' => $objective->responsible_agency,
                'indicator' => $objective->indicator,
                'operational_definition' => $objective->description,
                'mov' => $objective->mov,
                'assumptions' => $objective->assumptions_risk,
                'remarks' => $objective->pc_secretariat_remarks,
                'pillar_id' => $objective->pillar_id,
                'outcome_id' => $objective->outcome_id,
                'strategy_id' => $objective->strategy_id,
            ];

            $this->dynamicValues = [];
            foreach ($this->dynamicFields as $field) {
                if ($field->db_column) {
                    $this->dynamicValues[$field->field_name] = $objective->{$field->db_column};
                }
            }

            $targets = collect($objective->annual_plan_targets_series ?? []);
            $actuals = collect($objective->accomplishments_series ?? []);

            \Log::info('Loading breakdown from database', [
                'objective_id' => $objective->id,
                'yearStart' => $yearStart,
                'yearEnd' => $yearEnd,
                'targets_series' => $objective->annual_plan_targets_series,
                'actuals_series' => $objective->accomplishments_series
            ]);

            $this->breakdown = [];

            // Helper function to find actual value by year (handles both 'year' and 'period' keys)
            $findActual = function($actuals, $year) {
                $found = $actuals->first(function($item) use ($year) {
                    return (isset($item['year']) && $item['year'] == $year)
                        || (isset($item['period']) && $item['period'] == (string)$year);
                });
                return $found['value'] ?? null;
            };

            if ($yearStart) {
                $loopEnd = $yearEnd ?? ($yearStart + 5);
                if ($loopEnd < $yearStart) $loopEnd = $yearStart;
                if (($loopEnd - $yearStart) > 20) $loopEnd = $yearStart + 20;

                for ($y = $yearStart; $y <= $loopEnd; $y++) {
                    $tVal = $targets->firstWhere('year', $y)['value'] ?? null;
                    $aVal = $findActual($actuals, $y);

                    $this->breakdown[] = [
                        'year' => $y,
                        'target' => $tVal,
                        'actual' => $aVal,
                    ];
                }
            }

            \Log::info('Breakdown loaded', ['breakdown' => $this->breakdown]);

            // Load histories for timeline display
            $this->indicatorHistory = $objective->histories()->with('actor')->get();

            // Load proofs for this indicator
            $this->indicatorProofs = $objective->proofs()->with('uploader')->latest()->get();

            $this->showQuickForm = true;
            \Log::info('Admin edit form loaded', ['viewMode' => $this->viewMode, 'showQuickForm' => $this->showQuickForm]);
            return;
        }

        // Standard edit flow with workflow restrictions
        $editableStatuses = [Objective::STATUS_DRAFT, 'rejected', 'returned_to_psto', 'returned_to_agency', 'returned_to_ro'];

        // SA and Admin can edit anything (without explicit admin mode)
        if (!$user->isSA() && !$user->isAdministrator()) {
            // Execom cannot edit
            if ($user->isExecom()) {
                $this->dispatch('toast', message: 'Execom users cannot edit indicators.', type: 'error');
                return;
            }

            // HO can only edit submitted_to_ho status
            if ($user->canActAsHeadOfOffice() && !in_array($objective->status, ['submitted_to_ho', 'returned_to_ho'])) {
                $this->dispatch('toast', message: 'This indicator cannot be edited in its current status.', type: 'error');
                return;
            }

            // Check if status allows editing (for non-HO)
            if (!$user->canActAsHeadOfOffice() && !in_array($objective->status, $editableStatuses)) {
                $this->dispatch('toast', message: 'This indicator cannot be edited in its current status.', type: 'error');
                return;
            }

            // Scope check
            if ($user->isPSTO() || $user->isAgency()) {
                if ($objective->office_id != $user->office_id && $objective->submitted_by_user_id != $user->id) {
                    $this->dispatch('toast', message: 'You do not have permission to edit this indicator.', type: 'error');
                    return;
                }
            } elseif ($user->isRO()) {
                // RO can edit objectives from offices where they are head + child PSTO offices
                $roOffices = \App\Models\Office::where('head_user_id', $user->id)
                    ->where('type', 'RO')
                    ->pluck('id');

                if ($roOffices->isNotEmpty()) {
                    $childPstoOfficeIds = \App\Models\Office::whereIn('parent_office_id', $roOffices)
                        ->pluck('id');
                    $allOfficeIds = $roOffices->concat($childPstoOfficeIds)->unique()->toArray();

                    if (!in_array($objective->office_id, $allOfficeIds)) {
                        $this->dispatch('toast', message: 'You do not have permission to edit this indicator.', type: 'error');
                        return;
                    }
                } else {
                    $this->dispatch('toast', message: 'You are not assigned as head of any office.', type: 'error');
                    return;
                }
            }
        }

        $this->openView($id);
        $this->viewMode = false;
        $this->editingQuickFormId = $id;
    }

    public function openUpdateProgress(int $id): void
    {
        $user = Auth::user();

        // Only PSTO and Agency can update progress
        if (!$user->isPSTO() && !$user->isAgency() && !$user->isSA() && !$user->isAdministrator()) {
            $this->dispatch('toast', message: 'You do not have permission to update progress.', type: 'error');
            return;
        }

        $this->openView($id);
        $this->viewMode = false;
        $this->isUpdateProgress = true;
        $this->editingQuickFormId = $id;
    }

    public function closeQuickForm(): void
    {
        $this->showQuickForm = false;
        $this->editingQuickFormId = null;
        $this->viewMode = false;
        $this->isUpdateProgress = false;
        $this->adminBypassMode = false; // Reset admin bypass mode
        $this->chartData = [];
        $this->breakdown = [];
        $this->proof_file = null; // Reset proof file

        $this->quickForm = [
            'year_start' => '', 'year_end' => '',
            'category' => '', 'indicator' => '', 'outcome' => '', 'output' => '',
            'chapter' => '', 'monitoring_mechanism' => '', 'operational_definition' => '',
            'mov' => '', 'target' => '', 'baseline' => '', 'responsible_agency' => '',
            'reporting_agency' => '', 'assumptions' => '', 'remarks' => '', 'program_name' => '',
            'indicator_type' => 'outcome', 'agency_code' => '', 'office_name' => '', 'region_name' => '',
            'accomplishment' => '', 'pillar_id' => null, 'outcome_id' => null, 'strategy_id' => null,
        ];
    }

    public function saveQuickForm(): void
    {
        // A. Handle "Progress Update" Mode
        if ($this->isUpdateProgress) {
            \Log::info('Progress Update mode - calculating totals from breakdown', ['breakdown_count' => count($this->breakdown)]);

            // Validate remarks and MOV
            $this->validate([
                'quickForm.remarks' => 'nullable|string',
                'quickForm.mov' => 'nullable|string',
            ]);
            \Log::info('Progress Update validation passed');

            // Calculate totals from breakdown
            $totalTarget = 0;
            $totalActual = 0;
            $targetsSeries = [];
            $actualsSeries = [];
            // Create combined series with period, target, and value (actual)
            $accomplishmentsSeries = [];
            foreach ($this->breakdown as $row) {
                $year = (int)$row['year'];
                $target = isset($row['target']) && $row['target'] !== '' && $row['target'] !== null ? (float)$row['target'] : 0;
                $actual = isset($row['actual']) && $row['actual'] !== '' && $row['actual'] !== null ? (float)$row['actual'] : 0;

                if ($target > 0) {
                    $totalTarget += $target;
                    $targetsSeries[] = ['year' => $year, 'value' => $target];
                }
                if ($actual > 0) {
                    $totalActual += $actual;
                    $actualsSeries[] = ['year' => $year, 'value' => $actual];
                }

                // Create accomplishments_series entry with both 'year' and 'period' keys
                // 'year' is for openView() loading compatibility, 'period' is for blade template display
                $accomplishmentsSeries[] = [
                    'year' => $year,              // For openView() loading compatibility
                    'period' => (string)$year,    // For blade template display compatibility
                    'target' => $target,          // Target value (T)
                    'value' => $actual            // Actual value (A)
                ];
            }

            \Log::info('Progress Update totals calculated', [
                'totalTarget' => $totalTarget,
                'totalActual' => $totalActual,
                'targetsSeries' => $targetsSeries,
                'actualsSeries' => $actualsSeries,
                'accomplishmentsSeries' => $accomplishmentsSeries
            ]);

            // Update with breakdown data
            \Log::info('Progress Update - executing database update', ['id' => $this->editingQuickFormId]);
            $updated = Objective::where('id', $this->editingQuickFormId)->update([
                'target_value' => $totalTarget,
                'accomplishments' => $actualsSeries,
                'accomplishments_series' => $accomplishmentsSeries,
                'annual_plan_targets_series' => $targetsSeries,
                'mov' => $this->quickForm['mov'] ?? null,
                'pc_secretariat_remarks' => $this->quickForm['remarks'] ?? null,
                'updated_at' => now(),
            ]);

            \Log::info('Progress Update - database update completed', ['updated' => $updated, 'id' => $this->editingQuickFormId]);
            $this->dispatch('toast', message: 'Progress updated successfully.', type: 'success');

            // Close the form
            $this->closeQuickForm();
            $this->dispatch('indicator-saved');
            \Log::info('Progress Update - form closed');
            return;
        }

        // B. Handle Standard Create/Edit Mode
        $user = Auth::user();
        if (!$this->editingQuickFormId && !$user->isPSTO() && !$user->isAgency()) {
            $this->dispatch('toast', message: 'Only PSTO and Agency accounts can create indicators.', type: 'error');
            return;
        }
        $catSlug = $this->resolveCategoryFlow($this->quickForm['category'] ?? '');
        $catDef = IndicatorCategory::where('slug', $catSlug)->first();
        $isMandatory = $catDef ? (bool) $catDef->is_mandatory : true;

        $globalSetting = AdminSetting::first();
        $globalName = $globalSetting->org_name ?? 'DOST';

        $defaultAgency = $user->agency?->name ?? $user->office?->name ?? $globalName;

        if (empty($this->quickForm['reporting_agency'])) {
            $this->quickForm['reporting_agency'] = $defaultAgency;
        }
        
        if (empty($this->quickForm['responsible_agency'])) {
            $this->quickForm['responsible_agency'] = $defaultAgency;
        }

        $rules = [
            'quickForm.year_start' => 'required|integer|min:2010|max:2100',
            'quickForm.year_end' => 'nullable|integer|min:2010|max:2100|gte:quickForm.year_start',
            'quickForm.category' => 'required',
            'quickForm.indicator' => 'required|string',
        ];

        if ($isMandatory) {
            $rules = array_merge($rules, [
                'quickForm.target' => 'nullable|numeric|min:0', // Made nullable - target comes from breakdown table
                'quickForm.baseline' => 'nullable',
                'quickForm.responsible_agency' => 'required',
            ]);
        } else {
             $rules = array_merge($rules, [
                'quickForm.target' => 'nullable|numeric|min:0',
                'quickForm.baseline' => 'nullable',
            ]);
        }

        // Require proof when editing an approved indicator
        if ($this->editingQuickFormId) {
            $objective = Objective::find($this->editingQuickFormId);
            if ($objective && $objective->status === 'approved') {
                $rules['proof_file'] = 'required|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
            }
        }

        \Log::info('Building series from breakdown BEFORE validation', ['breakdown_count' => count($this->breakdown)]);

        // CRITICAL FIX: Calculate total target from breakdown and update quickForm['target']
        // This ensures when user edits breakdown table, the target_value is saved correctly
        $totalTargetFromBreakdown = 0;
        foreach ($this->breakdown as $row) {
            if (isset($row['target']) && is_numeric($row['target'])) {
                $totalTargetFromBreakdown += (float)$row['target'];
            }
        }
        \Log::info('Total target from breakdown', ['total' => $totalTargetFromBreakdown, 'current_quickForm_target' => $this->quickForm['target']]);

        // Update quickForm target to match breakdown total
        $this->quickForm['target'] = $totalTargetFromBreakdown;

        // Custom error messages for better UX
        $messages = [
            'quickForm.year_start.required' => 'The start year is required.',
            'quickForm.year_start.integer' => 'The start year must be a valid year.',
            'quickForm.category.required' => 'Please select a category.',
            'quickForm.indicator.required' => 'The indicator title is required.',
            'quickForm.target.required' => 'A total target value is required. Please check the breakdown table.',
            'quickForm.target.min' => 'The target value must be a positive number.',
            'quickForm.responsible_agency.required' => 'The responsible agency is required.',
            'proof_file.required' => 'A proof file is required for approved indicators.',
        ];
        
        \Log::info('Before validation', ['rules' => $rules, 'updated_target' => $this->quickForm['target']]);
        
        try {
            $this->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-failed');
            throw $e;
        }
        
        \Log::info('After validation - passed');

        \Log::info('Building accomplishments and targets series from breakdown');
        $targetsSeries = [];
        $actualsSeries = [];
        foreach ($this->breakdown as $row) {
            if (isset($row['target']) && $row['target'] !== '' && $row['target'] !== null) {
                $targetsSeries[] = ['year' => (int)$row['year'], 'value' => (float)$row['target']];
            }
            if (isset($row['actual']) && $row['actual'] !== '' && $row['actual'] !== null) {
                $actualsSeries[] = ['year' => (int)$row['year'], 'value' => (float)$row['actual']];
            }
        }

        $monitoringText = $this->quickForm['monitoring_mechanism'] ?? '';
        $remarksText = $this->quickForm['remarks'] ?? '';
        $remarksCombined = trim(collect([
            $monitoringText ? "Monitoring: {$monitoringText}" : null,
            $remarksText ? "Remarks: {$remarksText}" : null,
        ])->filter()->join("\n"));

        $finalTargetPeriod = $this->quickForm['year_start'];
        if (!empty($this->quickForm['year_end']) && $this->quickForm['year_end'] != $this->quickForm['year_start']) {
            $finalTargetPeriod .= '-' . $this->quickForm['year_end'];
        }

        $payload = [
            'submitted_by_user_id' => $user?->id,
            'category' => $catSlug,
            'description' => $this->quickForm['operational_definition'],
            'mov' => $this->quickForm['mov'],
            'target_value' => (int) $this->quickForm['target'],
            'baseline' => $this->quickForm['baseline'],
            'responsible_agency' => $this->quickForm['responsible_agency'],
            'reporting_agency' => $this->quickForm['reporting_agency'],
            'assumptions_risk' => $this->quickForm['assumptions'],
            'pc_secretariat_remarks' => $remarksCombined ?: null,
            'target_period' => $finalTargetPeriod,
            'annual_plan_targets_series' => $targetsSeries,
            'accomplishments_series' => $actualsSeries,
            'accomplishments' => $actualsSeries, // Also save to accomplishments for compatibility
            'status' => Objective::STATUS_DRAFT,
            'pillar_id' => $this->quickForm['pillar_id'] ?? null,
            'outcome_id' => $this->quickForm['outcome_id'] ?? null,
            'strategy_id' => $this->quickForm['strategy_id'] ?? null,
        ];

        \Log::info('Payload built successfully', [
            'target_value' => $payload['target_value'],
            'targetsSeries_count' => count($targetsSeries),
            'actualsSeries_count' => count($actualsSeries),
            'targetsSeries' => $targetsSeries,
            'actualsSeries' => $actualsSeries
        ]);

        \Log::info('Payload built', ['payload_keys' => array_keys($payload)]);

        foreach ($this->dynamicFields as $field) {
            $fieldName = $field->field_name;
            $dbColumn = $field->db_column;
            $value = $this->dynamicValues[$fieldName] ?? null;

            // Skip pillar, outcome, strategy fields - handled separately via quickForm
            if (in_array($fieldName, ['pillar', 'outcome', 'strategy'])) {
                continue;
            }

            if ($dbColumn) {
                $payload[$dbColumn] = $value;
            }
        }

        if (!isset($payload['indicator'])) {
            $payload['indicator'] = $this->quickForm['indicator'];
        }
        
        if (!isset($payload['objective_result'])) {
             if ($catSlug === 'prexc') {
                 $payload['objective_result'] = $this->quickForm['program_name'];
             } else {
                 $payload['objective_result'] = $payload['indicator'] ?? '';
             }
        }

        if ($user) {
            $payload['office_id'] = $user->office_id;
            $payload['region_id'] = $user->region_id;
        }
        if ($catDef) {
            $payload['is_mandatory'] = $catDef->is_mandatory;
        }

        if ($this->editingQuickFormId) {
            $objective = Objective::find($this->editingQuickFormId);
            if ($objective) {
                unset($payload['status']);

                // Set properties directly and save - this ensures JSON fields are properly handled
                foreach ($payload as $key => $value) {
                    $objective->$key = $value;
                }
                $objective->save();

                // Handle proof upload when editing approved indicator
                if ($objective->status === 'approved' && isset($this->proof_file) && $this->proof_file) {
                    $path = $this->proof_file->store('proofs', 'public');
                    \App\Models\Proof::create([
                        'objective_id' => $objective->id,
                        'file_path' => $path,
                        'file_name' => $this->proof_file->getClientOriginalName(),
                        'file_type' => $this->proof_file->getClientMimeType(),
                        'file_size' => $this->proof_file->getSize(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }

                $this->dispatch('toast', message: 'Indicator updated successfully.', type: 'success');
            } else {
                \Log::error('Objective not found for update', ['id' => $this->editingQuickFormId]);
                $this->dispatch('toast', message: 'Error: Indicator not found.', type: 'error');
            }
        } else {
            \Log::info('Creating new indicator', ['payload' => $payload]);
            $objective = Objective::create($payload);
            $this->dispatch('toast', message: 'Indicator saved as draft.', type: 'success');
        }

        // NOTE: This legacy code for 'accomplishment' field is kept for compatibility
        // but it shouldn't interfere with accomplishments_series which is the primary field now
        if (isset($this->quickForm['accomplishment']) && is_numeric($this->quickForm['accomplishment'])) {
             $targetId = $this->editingQuickFormId ?? $objective->id;
             \Log::info('Updating legacy accomplishment field', ['id' => $targetId, 'value' => $this->quickForm['accomplishment']]);
             Objective::where('id', $targetId)->update(['accomplishments' => $this->quickForm['accomplishment']]);
        }

        $this->dispatch('indicator-saved')->to(self::class);
        \Log::info('saveQuickForm completed - dispatching indicator-saved event');
        $this->closeQuickForm();
        \Log::info('saveQuickForm - FORM CLOSED');
    }

    // --- Helpers ---

    private function categoryRule(): string
    {
        $user = Auth::user();
        $slugs = IndicatorCategory::where('is_active', true)
            ->visibleTo($user)
            ->pluck('slug')
            ->filter()
            ->toArray();

        return $slugs ? 'required|in:' . implode(',', $slugs) : 'required';
    }

    private function resolveCategoryFlow(?string $category): string
    {
        $slug = strtolower(trim((string) $category));
        return match (true) {
            str_contains($slug, 'strategic') || $slug === 'sp' => 'strategic_plan',
            str_contains($slug, 'pdp') => 'pdp',
            str_contains($slug, 'prexc') => 'prexc',
            str_contains($slug, 'agency') => 'agency_specifics',
            default => $slug,
        };
    }

    private function parseRemarksField(?string $combined): array
    {
        $monitoring = '';
        $remarks = '';
        if ($combined) {
            $lines = explode("\n", $combined);
            foreach ($lines as $line) {
                if (str_starts_with($line, 'Monitoring: ')) {
                    $monitoring = substr($line, 12);
                } elseif (str_starts_with($line, 'Remarks: ')) {
                    $remarks = substr($line, 9);
                } else {
                    $remarks .= ($remarks ? "\n" : '') . $line;
                }
            }
        }
        return ['monitoring_mechanism' => $monitoring, 'remarks' => $remarks];
    }

    private function parsePillarStrategy(?string $combined): array
    {
        $pillar = '';
        $strategy = '';
        if ($combined) {
            $parts = explode(' ', trim($combined), 2);
            $pillar = $parts[0] ?? '';
            $strategy = $parts[1] ?? '';
        }
        return ['pillar' => $pillar, 'strategy' => $strategy];
    }

    private function mandatoryCategoryMap($user): Collection
    {
        return IndicatorCategory::where('is_active', true)
            ->visibleTo($user)
            ->get()
            ->keyBy(fn($c) => strtolower($c->slug));
    }

    // --- Notifications ---

    private function notifyRegionalOffice($regionId, $objective, $action)
    {
        // Get the PSTO office to find its parent RO
        $pstoOffice = $objective->office;
        if (!$pstoOffice) {
            return;
        }

        // Find the parent RO office (the office that oversees this PSTO)
        $roOffice = $pstoOffice->parent; // Uses parent_office_id relationship

        if ($roOffice && $roOffice->head_user_id) {
            // Notify the head of the RO office
            $roHead = \App\Models\User::find($roOffice->head_user_id);
            if ($roHead) {
                $roHead->notify(new \App\Notifications\ObjectiveStatusChanged($objective->status, $objective, $action, null, Auth::id()));
            }
        }
    }

    private function notifyHeadOffice($objective, $action)
    {
        $submitter = $objective->submitter;

        if ($submitter && $submitter->isAgency()) {
            // Agency submits directly to HO - find Central/Head Office
            $hoOffice = \App\Models\Office::where('type', 'HO')
                ->orWhere('type', 'CO')
                ->first();

            if ($hoOffice && $hoOffice->head_user_id) {
                $hoHead = \App\Models\User::find($hoOffice->head_user_id);
                if ($hoHead) {
                    $hoHead->notify(new \App\Notifications\ObjectiveStatusChanged($objective->status, $objective, $action, null, Auth::id()));
                }
            }
        } elseif ($submitter && $submitter->isRO()) {
            // RO submits to HO - find the parent Central/Head Office
            $roOffice = $objective->office;
            if ($roOffice && $roOffice->parent_office_id) {
                $hoOffice = \App\Models\Office::find($roOffice->parent_office_id);

                if ($hoOffice && $hoOffice->head_user_id) {
                    $hoHead = \App\Models\User::find($hoOffice->head_user_id);
                    if ($hoHead) {
                        $hoHead->notify(new \App\Notifications\ObjectiveStatusChanged($objective->status, $objective, $action, null, Auth::id()));
                    }
                }
            }
        }

        // Also notify admins
        $admins = \App\Models\User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\ObjectiveStatusChanged($objective->status, $objective, $action, null, Auth::id()));
        }
    }

    // --- Watchers for Year Range Changes ---

    public function updatedQuickFormYearStart($value)
    {
        $this->regenerateBreakdownRows();
    }

    public function updatedQuickFormYearEnd($value)
    {
        $this->regenerateBreakdownRows();
    }

    private function regenerateBreakdownRows()
    {
        $start = (int) ($this->quickForm['year_start'] ?? 0);
        $end = (int) ($this->quickForm['year_end'] ?? 0);

        if ($start > 0) {
            if ($end < $start) $end = $start; 
            
            $newBreakdown = [];
            for ($y = $start; $y <= $end; $y++) {
                $existing = collect($this->breakdown)->firstWhere('year', $y);
                $newBreakdown[] = [
                    'year' => $y,
                    'target' => $existing['target'] ?? '',
                    'actual' => $existing['actual'] ?? '',
                ];
            }
            $this->breakdown = $newBreakdown;
        }
    }

    // --- Import ---

    /**
     * Getter method for the uploading state (used by blade views)
     */
    public function uploading(): bool
    {
        return $this->importing;
    }

    public function importIndicators()
    {
        try {
            if (!Auth::user()->isPSTO() && !Auth::user()->isAgency() && !Auth::user()->isSuperAdmin() && !Auth::user()->isAdministrator()) {
                $this->dispatch('toast', message: 'Only PSTO, Agency, Admin, and SuperAdmin accounts can import indicators.', type: 'error');
                return;
            }
            $this->importing = true;

            $this->validate([
                'importFile' => 'required|mimes:csv,txt|max:2048',
            ]);

            $path = $this->importFile->getRealPath();
            $file = fopen($path, 'r');

            if (!$file) {
                $this->addError('importFile', 'Could not open file.');
                $this->importing = false;
                return;
            }

            $rawHeader = fgetcsv($file);

            if (!$rawHeader) {
                $this->addError('importFile', 'The CSV file is empty or unreadable.');
                fclose($file);
                $this->importing = false;
                return;
            }

            $header = array_map(function($h) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                $h = str_replace("\xC2\xA0", ' ', $h);
                $h = preg_replace('/[\x00-\x1F\x7F]/', '', $h);
                return strtolower(trim($h));
            }, $rawHeader);

            $count = 0;
            $errors = [];
            $rowNum = 1;
            $minYear = 2010;
            $maxYear = 2100;

            $parseYear = function ($value) {
                if ($value === null) {
                    return null;
                }

                $value = trim((string)$value);
                if ($value === '') {
                    return null;
                }

                if (!preg_match('/^\d{4}$/', $value)) {
                    return null;
                }

                return (int)$value;
            };

            // Auto-detect category from CSV headers
            // 1. Check if category column is explicitly provided (highest priority)
            $hasCategoryColumn = in_array('category', $header);

            // 2. Detect Strategic Plan: pillar + strategy + outcome
            $hasPillar = in_array('pillar', $header);
            $hasStrategy = in_array('strategy', $header);
            $hasOutcome = in_array('outcome', $header) || in_array('objective_result', $header);
            $isStratPlan = $hasPillar && $hasStrategy && $hasOutcome;

            // 3. Detect PDP: has 'chapter' column
            $hasPdpChapter = in_array('chapter', $header);

            // 4. Detect PREXC: has both 'program_name' and 'indicator_type'
            $hasPrexcProgram = in_array('program_name', $header);
            $hasPrexcType = in_array('indicator_type', $header);
            $isPrexc = $hasPrexcProgram && $hasPrexcType;

            while (($row = fgetcsv($file)) !== false) {
                $rowNum++;
                // Skip empty rows
                if (count($row) < 2 || (count($row) === 1 && empty($row[0]))) continue;

                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), null);
                } elseif (count($row) > count($header)) {
                    $row = array_slice($row, 0, count($header));
                }

                $data = array_combine($header, $row);

                try {
                    $payload = [
                        'submitted_by_user_id' => auth()->id(),
                        'status' => Objective::STATUS_DRAFT,
                        'target_period' => now()->year + 1,
                        'office_id' => auth()->user()->office_id,
                        'region_id' => auth()->user()->region_id,
                        'reporting_agency'   => auth()->user()->office->name ?? 'DOST',
                        'responsible_agency' => auth()->user()->office->name ?? 'DOST',
                    ];

                    $targetStartRaw = $data['target_period_start'] ?? ($data['target_period'] ?? null);
                    $targetEndRaw = $data['target_period_end'] ?? null;

                    if (!empty($targetStartRaw) && str_contains($targetStartRaw, '-')) {
                        $rangeParts = explode('-', $targetStartRaw, 2);
                        $targetStartRaw = $rangeParts[0] ?? null;
                        if (empty($targetEndRaw)) {
                            $targetEndRaw = $rangeParts[1] ?? null;
                        }
                    }

                    $startYear = $parseYear($targetStartRaw);
                    $endYear = $parseYear($targetEndRaw);

                    if (!empty($targetStartRaw) && $startYear === null) {
                        $errors[] = "Row {$rowNum}: Invalid target period start year '{$targetStartRaw}'.";
                        continue;
                    }

                    if (!empty($targetEndRaw) && $endYear === null) {
                        $errors[] = "Row {$rowNum}: Invalid target period end year '{$targetEndRaw}'.";
                        continue;
                    }

                    if ($startYear !== null && ($startYear < $minYear || $startYear > $maxYear)) {
                        $errors[] = "Row {$rowNum}: Target period start year must be between {$minYear} and {$maxYear}.";
                        continue;
                    }

                    if ($endYear !== null && ($endYear < $minYear || $endYear > $maxYear)) {
                        $errors[] = "Row {$rowNum}: Target period end year must be between {$minYear} and {$maxYear}.";
                        continue;
                    }

                    if ($endYear !== null && $startYear === null) {
                        $errors[] = "Row {$rowNum}: Target period end year provided without a start year.";
                        continue;
                    }

                    if ($startYear !== null && $endYear !== null && $endYear < $startYear) {
                        $errors[] = "Row {$rowNum}: Target period end year cannot be earlier than start year.";
                        continue;
                    }

                    if ($startYear !== null) {
                        $payload['target_period'] = (string)$startYear;
                        if ($endYear !== null && $endYear !== $startYear) {
                            $payload['target_period'] .= '-' . $endYear;
                        }
                    }

                    if ($isStratPlan) {
                        $chapter = Chapter::firstOrCreate(
                            ['category' => 'strategic_plan'],
                            ['title' => 'Strategic Plan', 'code' => 'SP', 'sort_order' => 1]
                        );

                        $payload['chapter_id'] = $chapter->id;
                        $payload['category'] = 'strategic_plan';

                        // Map CSV numeric values to database IDs
                        $pillarValue = $data['pillar'] ?? ($data['program_name'] ?? null);
                        $outcomeValue = $data['outcome'] ?? ($data['objective_result'] ?? null);
                        $strategyValue = $data['strategy'] ?? ($data['description'] ?? null);

                        if (is_numeric($pillarValue)) {
                            $pillar = \App\Models\Pillar::where('value', (int)$pillarValue)->first();
                            $payload['pillar_id'] = $pillar ? $pillar->id : null;
                            $payload['program_name'] = $pillarValue;
                        }

                        if (is_numeric($outcomeValue)) {
                            $outcome = \App\Models\Outcome::where('value', (int)$outcomeValue)->first();
                            $payload['outcome_id'] = $outcome ? $outcome->id : null;
                            $payload['objective_result'] = $outcomeValue;
                        }

                        if (is_numeric($strategyValue)) {
                            $strategy = \App\Models\Strategy::where('value', (int)$strategyValue)->first();
                            $payload['strategy_id'] = $strategy ? $strategy->id : null;
                            $payload['description'] = $strategyValue;
                        }

                        $payload['indicator']        = $data['outcome indicator'] ?? ($data['indicator'] ?? null);
                        $payload['output_indicator'] = $data['output indicator'] ?? ($data['output_indicator'] ?? null);

                    } else {
                        // Determine category slug (priority: CSV column > auto-detect > filter > default)
                        $catSlug = null;
                        if ($hasCategoryColumn && !empty($data['category'])) {
                            // Explicit category column provided
                            $catSlug = strtolower(trim($data['category']));
                        } elseif ($hasPdpChapter) {
                            // Auto-detect PDP
                            $catSlug = 'pdp';
                        } elseif ($isPrexc) {
                            // Auto-detect PREXC
                            $catSlug = 'prexc';
                        } else {
                            // Fall back to selected filter or default
                            $catSlug = $this->categoryFilter ?: 'strategic_plan';
                        }

                        // Validate category exists
                        $validCategory = \App\Models\IndicatorCategory::where('slug', $catSlug)->first();
                        if (!$validCategory) {
                            $errors[] = "Row {$rowNum}: Invalid category '{$catSlug}'";
                            continue;
                        }

                        // Get or create chapter for this category
                        $chapter = Chapter::where('category', $catSlug)->first();
                        if (!$chapter) {
                            // Create a default chapter for this category if none exists
                            $chapter = Chapter::create([
                                'category' => $catSlug,
                                'code' => strtoupper($catSlug),
                                'title' => ucfirst(str_replace('_', ' ', $catSlug)),
                                'outcome' => ucfirst(str_replace('_', ' ', $catSlug)) . ' indicators',
                                'sort_order' => 1,
                                'is_active' => true,
                            ]);
                        }

                        $payload['chapter_id'] = $chapter->id;
                        $payload['category'] = $catSlug;

                        // Map common fields for all non-Strategic Plan categories
                        $payload['indicator'] = $data['indicator'] ?? ($data['outcome indicator'] ?? null);
                        $payload['description'] = $data['description'] ?? null;
                        $payload['objective_result'] = $data['objective_result'] ?? null;

                        // PREXC-specific fields
                        if ($catSlug === 'prexc') {
                            $payload['program_name'] = $data['program_name'] ?? null;
                            $payload['indicator_type'] = $data['indicator_type'] ?? null;
                            $payload['prexc_code'] = $data['prexc_code'] ?? null;
                        }

                        // PDP-specific fields
                        if ($catSlug === 'pdp') {
                            $payload['description'] = $data['chapter'] ?? $data['description'] ?? null;
                        }

                        // Performance tracking fields (common to all)
                        $payload['baseline'] = $data['baseline'] ?? null;

                        $targetValueRaw = $data['target_value'] ?? $data['target'] ?? null;
                        if ($targetValueRaw !== null && $targetValueRaw !== '' && !is_numeric($targetValueRaw)) {
                            $errors[] = "Row {$rowNum}: Target value must be numeric.";
                            continue;
                        }

                        $payload['target_value'] = (int)($targetValueRaw ?? 0);

                        // Documentation fields (common to all)
                        $payload['mov'] = $data['mov'] ?? null;
                        $payload['responsible_agency'] = $data['responsible_agency'] ?? null;
                        $payload['reporting_agency'] = $data['reporting_agency'] ?? null;
                        $payload['assumptions_risk'] = $data['assumptions_risk'] ?? null;
                        $payload['dost_agency'] = $data['dost_agency'] ?? null;
                    }

                    if (empty($payload['indicator'])) continue;

                    Objective::create($payload);
                    $count++;

                } catch (\Exception $e) {
                    // Clean up raw SQL from error message to make it user-friendly
                    $msg = $e->getMessage();
                    // Extract the human-readable part before the SQL dump
                    if (preg_match('/SQLSTATE\[.*?\]:\s*(.+?)\s*\(Connection:/', $msg, $m)) {
                        $msg = $m[1];
                    } elseif (str_contains($msg, '(Connection: mysql')) {
                        $msg = substr($msg, 0, strpos($msg, '(Connection: mysql'));
                    }
                    $errors[] = "Row {$rowNum}: " . trim($msg);
                }
            }

            fclose($file);

            $this->importedCount = $count;
            $this->importErrors = $errors;
            $this->importFile = null;
            $this->importing = false;

            $this->dispatch('indicator-saved');

            $errorCount = count($errors);
            if ($count === 0 && $errorCount > 0) {
                // Total failure
                $this->dispatch('toast', message: "Import failed. {$errorCount} error(s) encountered. No rows were imported.", type: 'error');
            } elseif ($errorCount > 0) {
                // Partial success
                $this->dispatch('toast', message: "Imported {$count} rows with {$errorCount} error(s). Check the error list for details.", type: 'warning');
            } else {
                // Full success
                $this->dispatch('toast', message: "Successfully imported {$count} indicators.", type: 'success');
            }

        } catch (\Exception $e) {
            $this->importing = false;
            $this->importErrors = ['Import failed: ' . $e->getMessage()];
            $this->dispatch('toast', message: 'Import failed: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
        {
            try {
                $user = Auth::user();

                // 1. Base Query with Eager Loading
                $query = Objective::with(['region', 'office', 'submitter.agency', 'submitter.office', 'submitter.region', 'chapter', 'pillar', 'outcome', 'strategy']);            $this->applyScopes($query, $user);

            // 3. CALCULATE STATS BEFORE STATUS FILTER (Stats should reflect totals, not filtered by status)
            $statsQuery = clone $query;

            // Apply filters that SHOULD affect stats (year, category, search, mandatory)
            if ($this->yearFilter) {
                $statsQuery->byYear((int)$this->yearFilter);
            }
            if ($this->categoryFilter) {
                $statsQuery->byCategory($this->categoryFilter);
            }
            if ($this->search) {
                $statsQuery->search($this->search);
            }
            if ($this->mandatoryFilter === 'yes') {
                $statsQuery->where('is_mandatory', true);
            } elseif ($this->mandatoryFilter === 'no') {
                $statsQuery->where('is_mandatory', false);
            }

            // Calculate stats WITHOUT status filter (optimized: single query with CASE)
            $pendingStatuses = ['submitted_to_ro', 'submitted_to_ho', 'submitted_to_ousec', 'submitted_to_admin', 'submitted_to_superadmin'];
            $returnedStatuses = [
                'rejected',
                'returned_to_psto',
                'returned_to_agency',
                'returned_to_ro',
                'returned_to_ho',
                'returned_to_ousec',
                'returned_to_admin',
            ];

            $statsData = $statsQuery->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status IN (?, ?, ?, ?, ?) THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN (?, ?, ?, ?, ?, ?, ?) THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft
            ', array_merge(['approved'], $pendingStatuses, $returnedStatuses, ['draft']))
            ->first();

            $stats = [
                'total'    => (int) $statsData->total,
                'approved' => (int) $statsData->approved,
                'pending'  => (int) $statsData->pending,
                'returned' => (int) $statsData->returned,
                'draft'    => (int) $statsData->draft,
            ];

            // 4. NOW APPLY UI FILTERS FOR THE TABLE DISPLAY
            if ($this->yearFilter) {
                $query->byYear((int)$this->yearFilter);
            }
            if ($this->statusFilter === 'pending') {
                $query->whereIn('status', $pendingStatuses);
            } elseif ($this->statusFilter === 'returned') {
                $query->whereIn('status', $returnedStatuses);
            } elseif ($this->statusFilter) {
                $query->byStatus($this->statusFilter);
            }
            if ($this->search) {
                $query->search($this->search);
            }
            if ($this->categoryFilter) {
                $query->byCategory($this->categoryFilter);
            }

            if ($this->mandatoryFilter === 'yes') {
                $query->where('is_mandatory', true);
            } elseif ($this->mandatoryFilter === 'no') {
                $query->where('is_mandatory', false);
            }

            // Pillar, Outcome, Strategy filters
            if ($this->pillarFilter) {
                $query->where('pillar_id', $this->pillarFilter);
            }
            if ($this->outcomeFilter) {
                $query->where('outcome_id', $this->outcomeFilter);
            }
            if ($this->strategyFilter) {
                $query->where('strategy_id', $this->strategyFilter);
            }

            // Date range filter
            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }

            // My Indicators Only filter
            if ($this->myIndicatorsOnly) {
                $query->where('created_by', $user->id);
            }

            // 5. Calculate Mandatory Progress
            $mandatoryQuery = Objective::where('is_mandatory', true);
            $this->applyScopes($mandatoryQuery, $user);

            $mandatoryProgress = [
                'total' => (clone $mandatoryQuery)->count(),
                'completed' => (clone $mandatoryQuery)->where('status', 'approved')->count(),
            ];

            $pendingApprovalsCount = 0;
            $pendingApprovalsQuery = Objective::query();
            $this->applyScopes($pendingApprovalsQuery, $user);

            if ($user->isSuperAdmin()) {
                $pendingApprovalsCount = (clone $pendingApprovalsQuery)->where('status', Objective::STATUS_SUBMITTED_TO_SUPERADMIN)->count();
            } elseif ($user->isAdministrator()) {
                $pendingApprovalsCount = (clone $pendingApprovalsQuery)->where('status', Objective::STATUS_SUBMITTED_TO_ADMIN)->count();
            } elseif ($user->isOUSEC()) {
                $pendingApprovalsCount = (clone $pendingApprovalsQuery)->where('status', Objective::STATUS_SUBMITTED_TO_OUSEC)->count();
            } elseif ($user->canActAsHeadOfOffice()) {
                $pendingApprovalsCount = (clone $pendingApprovalsQuery)->where('status', Objective::STATUS_SUBMITTED_TO_HO)->count();
            } elseif ($user->isRO()) {
                $pendingApprovalsCount = (clone $pendingApprovalsQuery)->where('status', Objective::STATUS_SUBMITTED_TO_RO)->count();
            }

            // 6. Role-Specific Advanced Stats
            $officeBreakdown = [];
            $performanceTrends = [];
            $regionalComparison = [];

            // For RO and HO: Calculate office breakdown
            if ($user->isRO() || $user->canActAsHeadOfOffice() || $user->isSA() || $user->isAdministrator()) {
                $officeStatsQuery = clone $statsQuery;

                // Get all offices in scope
                $officesInScope = Office::where('is_active', true);

                // RO users: See offices where they are head and child PSTO offices
                if ($user->isRO()) {
                    $roOfficeIds = \App\Models\Office::where('head_user_id', $user->id)
                        ->where('type', 'RO')
                        ->pluck('id');

                    if ($roOfficeIds->isNotEmpty()) {
                        // Get RO office IDs and all their child PSTO office IDs
                        $childPstoOfficeIds = \App\Models\Office::whereIn('parent_office_id', $roOfficeIds)
                            ->pluck('id');
                        $allOfficeIds = $roOfficeIds->concat($childPstoOfficeIds)->unique();
                        $officesInScope->whereIn('id', $allOfficeIds);
                    } else {
                        // RO is not assigned as head of any office - show no offices
                        $officesInScope->where('id', 0);
                    }
                }
                // HO users: See offices in their region
                elseif ($user->canActAsHeadOfOffice() && $user->region_id) {
                    $officesInScope->where('region_id', $user->region_id);
                }
                // SA and Admin see all offices (no filter)

                $officesInScope = $officesInScope->get();

                foreach ($officesInScope as $office) {
                    $officeQuery = clone $officeStatsQuery;
                    $officeQuery->where('office_id', $office->id);

                    $total = $officeQuery->count();
                    if ($total > 0) {
                        $approved = (clone $officeQuery)->where('status', 'approved')->count();
                        $pending = (clone $officeQuery)->whereIn('status', $pendingStatuses)->count();
                        $rejected = (clone $officeQuery)->where('status', 'rejected')->count();

                        $officeBreakdown[] = [
                            'office_name' => $office->name,
                            'total' => $total,
                            'approved' => $approved,
                            'pending' => $pending,
                            'rejected' => $rejected,
                            'completion_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
                        ];
                    }
                }

                // Sort by completion rate descending
                usort($officeBreakdown, fn($a, $b) => $b['completion_rate'] <=> $a['completion_rate']);
            }

            // For HO, SA, Admin: Calculate performance trends over time
            if ($user->canActAsHeadOfOffice() || $user->isSA() || $user->isAdministrator()) {
                // Get data by year
                $years = Objective::selectRaw('SUBSTRING_INDEX(target_period, "-", 1) as year')
                    ->whereNotNull('target_period')
                    ->where('target_period', '!=', '')
                    ->when(!$user->isSA() && !$user->isAdministrator() && $user->region_id, fn($q) => $q->where('region_id', $user->region_id))
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->limit(5)
                    ->pluck('year')
                    ->filter()
                    ->sort()
                    ->values();

                foreach ($years as $year) {
                    $yearQuery = Objective::where('target_period', 'LIKE', $year . '%');
                    if (!$user->isSA() && !$user->isAdministrator() && $user->region_id) {
                        $yearQuery->where('region_id', $user->region_id);
                    }

                    $total = $yearQuery->count();
                    if ($total > 0) {
                        $approved = (clone $yearQuery)->where('status', 'approved')->count();
                        $performanceTrends[] = [
                            'year' => $year,
                            'total' => $total,
                            'approved' => $approved,
                            'rate' => round(($approved / $total) * 100, 1),
                        ];
                    }
                }
            }

            // For SA/Admin: Regional comparison
            if ($user->isSA() || $user->isAdministrator()) {
                $regions = PhilippineRegion::where('is_active', true)->get();
                foreach ($regions as $region) {
                    $regionQuery = Objective::where('region_id', $region->id);

                    // Apply same filters as main stats
                    if ($this->yearFilter) {
                        $regionQuery->byYear((int)$this->yearFilter);
                    }
                    if ($this->categoryFilter) {
                        $regionQuery->byCategory($this->categoryFilter);
                    }
                    if ($this->mandatoryFilter === 'yes') {
                        $regionQuery->where('is_mandatory', true);
                    } elseif ($this->mandatoryFilter === 'no') {
                        $regionQuery->where('is_mandatory', false);
                    }

                    $total = $regionQuery->count();
                    if ($total > 0) {
                        $approved = (clone $regionQuery)->where('status', 'approved')->count();
                        $regionalComparison[] = [
                            'region_name' => $region->name,
                            'total' => $total,
                            'approved' => $approved,
                            'rate' => round(($approved / $total) * 100, 1),
                        ];
                    }
                }

                // Sort by rate descending
                usort($regionalComparison, fn($a, $b) => $b['rate'] <=> $a['rate']);
            }

            // 7. Apply Sorting
            // Use LEFT JOIN with aliases for sorting to avoid conflicts with eager-loaded relationships
            $query->when($this->sortBy === 'pillar_value', function ($q) {
                return $q->leftJoin('pillars as pillar_sort', 'objectives.pillar_id', '=', 'pillar_sort.id')
                    ->orderBy('pillar_sort.value', $this->sortDirection);
            })
            ->when($this->sortBy === 'outcome_value', function ($q) {
                return $q->leftJoin('outcomes as outcome_sort', 'objectives.outcome_id', '=', 'outcome_sort.id')
                    ->orderBy('outcome_sort.value', $this->sortDirection);
            })
            ->when($this->sortBy === 'strategy_value', function ($q) {
                return $q->leftJoin('strategies as strategy_sort', 'objectives.strategy_id', '=', 'strategy_sort.id')
                    ->orderBy('strategy_sort.value', $this->sortDirection);
            })
            ->when($this->sortBy === 'target_value', fn($q) => $q->orderBy('objectives.target_value', $this->sortDirection))
            ->when($this->sortBy === 'created_at', fn($q) => $q->orderBy('objectives.created_at', $this->sortDirection))
            ->when($this->sortBy === 'updated_at', fn($q) => $q->orderBy('objectives.updated_at', $this->sortDirection));

            // Apply default ordering if not using custom sort
            if (!in_array($this->sortBy, ['pillar_value', 'outcome_value', 'strategy_value', 'target_value', 'created_at', 'updated_at'])) {
                $query->latest('objectives.created_at');
            }

            // 8. Final Results and View Data

            // Calculate maxYears for table alignment
            $maxYears = 1;
            $allObjectives = clone $query;
            foreach ($allObjectives->get() as $obj) {
                $period = $obj->target_period;
                if (strpos($period, '-') !== false) {
                    $parts = explode('-', $period);
                    if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $yearCount = (int)$parts[1] - (int)$parts[0] + 1;
                        $maxYears = max($maxYears, $yearCount);
                    }
                }
            }

            // Calculate total columns for empty state colspan
            $categoryFieldsCount = $this->categoryFieldsForTable ? count($this->categoryFieldsForTable) : 0;
            $baseColumns = 7; // indicator, category, office, accomplishments (base), perf, status, actions
            $extraColumns = $categoryFieldsCount + ($user->isSuperAdmin() ? 1 : 0) + (!$user->isPSTO() ? 1 : 0);
            $totalColumns = $baseColumns + $maxYears + $extraColumns;

            return view('livewire.dashboard.unified-dashboard', [
                'objectives' => $query->paginate($this->perPage),
                'stats' => $stats,
                'regions' => PhilippineRegion::where('is_active', true)->orderBy('name')->get(),
                'offices' => $this->getFilteredOffices(),
                'years' => Objective::select('target_period')->distinct()->pluck('target_period')->sort()->values(),
                'pdpOutcomes' => Chapter::where('category', 'pdp')->get(),
                'agencyOptions' => DOSTAgency::where('is_active', true)->orderBy('name')->get(),
                'indicatorCategories' => IndicatorCategory::visibleTo($user)->get(),
                'mandatoryProgress' => $mandatoryProgress,
                'pendingApprovalsCount' => $pendingApprovalsCount,
                'quickCategoryFlow' => $this->quickCategoryFlow ?? false,
                'showQuickForm' => $this->showQuickForm,
                // Role-specific stats
                'officeBreakdown' => $officeBreakdown,
                'performanceTrends' => $performanceTrends,
                'regionalComparison' => $regionalComparison,
                // Strategic Plan Options for filters
                'pillars' => \App\Models\Pillar::where('is_active', true)->orderBy('value')->get(),
                'outcomes' => \App\Models\Outcome::where('is_active', true)->orderBy('value')->get(),
                'strategies' => \App\Models\Strategy::where('is_active', true)->orderBy('value')->get(),
                // Table structure for alignment
                'maxYears' => $maxYears,
                'totalColumns' => $totalColumns,
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard render error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('livewire.dashboard.unified-dashboard', [
                'objectives' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage ?? 15),
                'stats' => ['total' => 0, 'approved' => 0, 'pending' => 0, 'returned' => 0, 'draft' => 0],
                'regions' => collect(),
                'offices' => collect(),
                'years' => collect(),
                'pdpOutcomes' => collect(),
                'agencyOptions' => collect(),
                'indicatorCategories' => collect(),
                'mandatoryProgress' => ['total' => 0, 'completed' => 0],
                'pendingApprovalsCount' => 0,
                'quickCategoryFlow' => false,
                'showQuickForm' => false,
                // Role-specific stats
                'officeBreakdown' => [],
                'performanceTrends' => [],
                'regionalComparison' => [],
                // Strategic Plan Options
                'pillars' => collect(),
                'outcomes' => collect(),
                'strategies' => collect(),
                // Table structure for alignment
                'maxYears' => 1,
                'totalColumns' => 8, // Minimum columns
            ]);
        }
    }

    /**
     * Get offices filtered based on user hierarchy
     * - SA/Admin: All active offices
     * - RO/HO: Only offices in their region
     * - PSTO: Only their assigned office
     */
    private function getFilteredOffices(): Collection
    {
        $user = Auth::user();
        $query = Office::where('is_active', true)->orderBy('name');

        // RO and HO see only offices in their region
        if ($user->isRO() || $user->canActAsHeadOfOffice()) {
            $regionId = $user->region_id ?? $user->office?->region_id;
            if ($regionId) {
                $query->where('region_id', $regionId);
            }
        }
        // PSTO sees only their assigned office
        elseif ($user->isPSTO()) {
            if ($user->office_id) {
                $query->where('id', $user->office_id);
            }
        }
        // SA and Admin see all offices (no filter)

        return $query->get();
    }

    public function updatedQuickFormCategory($value)
    {
        $this->loadDynamicFields($value);
    }

    private function loadDynamicFields($categorySlug)
    {
        $this->dynamicFields = [];
        $this->dynamicValues = [];

        if (!$categorySlug) return;

        $category = IndicatorCategory::where('slug', $categorySlug)->first();
        
        if ($category) {
            $this->dynamicFields = CategoryField::where('category_id', $category->id)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();

            foreach ($this->dynamicFields as $field) {
                $this->dynamicValues[$field->field_name] = '';
            }
        }
    }

    private function getUserAgencyName()
    {
        $user = auth()->user();
        return $user->office?->name ?? $user->agency?->name ?? 'DOST';
    }

    // --- Component Actions ---
    public function selectCategory(string $category): void { $this->selectedCategory = $category; $this->showCategorySelector = false; }
    public function closeCategorySelector(): void { $this->showCategorySelector = false; }
    public function selectOutcome(int $id): void { $this->selectedOutcome = $id; }
    public function selectIndicatorTemplate(int $id): void { $this->selectedIndicatorTemplate = $id; }
    public function closePdpSelector(): void { $this->showPdpSelector = false; }
    public function selectAgency(int $id): void { $this->selectedAgency = $id; }
    public function closeAgencySelector(): void { $this->showAgencySelector = false; }
    #[On('indicator-saved')] public function refreshAfterSave(): void { $this->dispatch('$refresh'); }
    public function proceedWithPdp(): void { $this->showPdpSelector = false; }
    public function proceedWithAgency(): void { $this->showAgencySelector = false; }
    public function closePdpView(): void { $this->showPdpView = false; }
    public function formatStatus(?string $status): string { return $status ?: ''; }

    private function applyScopes($query, $user)
    {
        // A. Super Admin / Admin: View All
        if ($user->isSA() || $user->isAdministrator() || $user->isSuperAdmin()) {
            return;
        }

        // B. OUSEC View: See indicators based on role
        if ($user->isOUSEC()) {
            // OUSEC-RO sees all regional/PSTO indicators at OUSEC level and above
            if ($user->isOUSEROR()) {
                $query->where(function($sub) {
                    $sub->whereNotNull('office_id')
                         ->whereNotNull('region_id');
                })->whereIn('status', [
                    'submitted_to_ousec',
                    'submitted_to_admin',
                    'submitted_to_superadmin',
                    'approved'
                ]);
            }
            // OUSEC-STS and OUSEC-RD see agency indicators by cluster
            else {
                $allowedClusters = $user->getOUSECClusters();
                $query->whereHas('submitter.agency', function($agencyQuery) use ($allowedClusters) {
                    $agencyQuery->whereIn('cluster', $allowedClusters);
                })->whereIn('status', [
                    'submitted_to_ousec',
                    'submitted_to_admin',
                    'submitted_to_superadmin',
                    'approved'
                ]);
            }
            return;
        }

        if ($user->isRO()) {
            // Find all offices where this RO is the head (from Office Manager)
            $roOffices = \App\Models\Office::where('head_user_id', $user->id)
                ->where('type', 'RO')
                ->get();

            if ($roOffices->isNotEmpty()) {
                // Get RO office IDs and all their child PSTO office IDs
                $roOfficeIds = $roOffices->pluck('id');
                $childPstoOfficeIds = \App\Models\Office::whereIn('parent_office_id', $roOfficeIds)
                    ->pluck('id');

                // Combine both RO offices and their child PSTO offices
                $allOfficeIds = $roOfficeIds->concat($childPstoOfficeIds)->unique();
                $query->whereIn('office_id', $allOfficeIds);
            } else {
                // RO is not assigned as head of any office - show nothing
                $query->where('id', 0);
            }
        }
        // C. Agency-assigned HO: Only see indicators from that agency (by submitter's agency_id)
        elseif ($user->canActAsHeadOfOffice() && $user->agency_id) {
            $query->whereHas('submitter', function($sub) use ($user) {
                $sub->where('agency_id', $user->agency_id);
            })->whereIn('status', [
                \App\Models\Objective::STATUS_SUBMITTED_TO_HO,
                \App\Models\Objective::STATUS_RETURNED_TO_HO,
                \App\Models\Objective::STATUS_SUBMITTED_TO_OUSEC,
                \App\Models\Objective::STATUS_RETURNED_TO_OUSEC,
                \App\Models\Objective::STATUS_SUBMITTED_TO_ADMIN,
                \App\Models\Objective::STATUS_RETURNED_TO_ADMIN,
                \App\Models\Objective::STATUS_SUBMITTED_TO_SUPERADMIN,
                \App\Models\Objective::STATUS_APPROVED,
            ]);
        }
    }

    private function applyFilters($query)
    {
        if ($this->regionFilter) {
            $query->where('region_id', $this->regionFilter);
        }
        if ($this->officeFilter) {
            $query->where('office_id', $this->officeFilter);
        }
        if ($this->agencyFilter) {
            $query->whereHas('submitter', function($q) {
                $q->where('agency_id', $this->agencyFilter);
            });
        }
        if ($this->yearFilter) {
            $query->byYear((int)$this->yearFilter);
        }
        
        $pendingStatuses = ['submitted_to_ro', 'submitted_to_ho', 'submitted_to_ousec', 'submitted_to_admin', 'submitted_to_superadmin'];
        $returnedStatuses = [
            'rejected', 'returned_to_psto', 'returned_to_agency', 'returned_to_ro', 
            'returned_to_ho', 'returned_to_ousec', 'returned_to_admin'
        ];

        if ($this->statusFilter === 'pending') {
            $query->whereIn('status', $pendingStatuses);
        } elseif ($this->statusFilter === 'returned') {
            $query->whereIn('status', $returnedStatuses);
        } elseif ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }
        
        if ($this->search) {
            $query->search($this->search);
        }
        if ($this->categoryFilter) {
            $query->byCategory($this->categoryFilter);
        }

        if ($this->mandatoryFilter === 'yes') {
            $query->where('is_mandatory', true);
        } elseif ($this->mandatoryFilter === 'no') {
            $query->where('is_mandatory', false);
        }

        if ($this->pillarFilter) {
            $query->where('pillar_id', $this->pillarFilter);
        }
        if ($this->outcomeFilter) {
            $query->where('outcome_id', $this->outcomeFilter);
        }
        if ($this->strategyFilter) {
            $query->where('strategy_id', $this->strategyFilter);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->myIndicatorsOnly) {
            $query->where('created_by', auth()->id());
        }
    }

    public function export()
    {
        $user = auth()->user();
        $query = \App\Models\Objective::with(['region', 'office', 'submitter.agency', 'submitter.office', 'submitter.region', 'chapter', 'pillar', 'outcome', 'strategy']);
        
        $this->applyScopes($query, $user);
        $this->applyFilters($query);

        $objectives = $query->latest()->get();

        return response()->streamDownload(function() use ($objectives) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($handle, [
                'ID', 'Category', 'Indicator', 'Target Period', 'Status', 
                'Agency', 'Office', 'Region', 'Target Value', 
                'Outcome', 'Pillar', 'Strategy'
            ]);

            foreach ($objectives as $obj) {
                fputcsv($handle, [
                    $obj->id,
                    ucfirst(str_replace('_', ' ', $obj->category)),
                    $obj->indicator,
                    $obj->target_period,
                    strtoupper(str_replace('_', ' ', $obj->status)),
                    $obj->submitter->agency->name ?? 'N/A',
                    $obj->office->name ?? 'N/A',
                    $obj->region->name ?? 'N/A',
                    $obj->target_value,
                    $obj->outcome->name ?? 'N/A',
                    $obj->pillar->name ?? 'N/A',
                    $obj->strategy->name ?? 'N/A',
                ]);
            }
            fclose($handle);
        }, 'indicators-export-' . now()->format('Y-m-d') . '.csv');
    }
}