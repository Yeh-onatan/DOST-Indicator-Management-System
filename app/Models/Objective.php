<?php

namespace App\Models;

use App\Constants\AgencyConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Objective Model (Canonical)
 *
 * This is the single authoritative model for the `objectives` table.
 * The old `Indicator` model is now a thin alias that extends this class.
 *
 * Table: objectives
 */
class Objective extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'objectives';

    protected $fillable = [
        'submitted_by_user_id',
        'office_id',
        'region_id',
        'chapter_id',
        'pillar_id',
        'outcome_id',
        'strategy_id',
        'category',
        'indicator',
        'objective_result',
        'description',
        'output_indicator',
        'target_value',
        'baseline',
        'accomplishments',
        'annual_plan_targets_series',
        'accomplishments_series',
        'status',
        'is_locked',
        'target_period',
        'rejection_reason',
        'remarks',
        'program_name',
        'responsible_agency',
        'reporting_agency',
        'assumptions_risk',
        'mov',
        'pc_secretariat_remarks',
        'owner_id',
        'priority',
        'created_by',
        'updated_by',
        'review_notes',
        'corrections_required',
        'is_mandatory',
        'submitted_to_ro_at',
        'submitted_to_ho_at',
        'submitted_to_ousec_at',
        'submitted_to_admin_at',
        'submitted_to_superadmin_at',
        'approved_at',
        'rejected_at',
        'approved_by',
        'rejected_by',
        'agency_id',
        'dost_agency',
        'agency_code',
        'sp_id',
        'admin_name',
        'indicator_type',
        'annual_plan_targets',
    ];

    protected $casts = [
        'target_value' => 'integer',
        'corrections_required' => 'array',
        'accomplishments' => 'array',
        'annual_plan_targets_series' => 'array',
        'accomplishments_series' => 'array',
        'is_mandatory' => 'boolean',
        'is_locked' => 'boolean',
        'submitted_to_ro_at' => 'datetime',
        'submitted_to_ho_at' => 'datetime',
        'submitted_to_ousec_at' => 'datetime',
        'submitted_to_admin_at' => 'datetime',
        'submitted_to_superadmin_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ============================================================
    // Eager-load relationships to prevent N+1 queries (lazy loading fix)
    //
    // WHY: Without this, every time you access $objective->region,
    // $objective->office, or $objective->submitter, Laravel fires
    // a separate SQL query per record. With 100 records on a page,
    // that's 300+ extra queries (the "N+1" problem).
    //
    // By listing them here, Laravel loads them all in ONE query
    // (using a WHERE IN (...) clause), which is dramatically faster.
    // ============================================================
    protected $with = ['regionRelation', 'office', 'submitter'];

    // --- STATUS CONSTANTS (normalized to lowercase) ---
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED_TO_RO = 'submitted_to_ro';
    const STATUS_RETURNED_TO_PSTO = 'returned_to_psto';
    const STATUS_RETURNED_TO_AGENCY = 'returned_to_agency';
    const STATUS_SUBMITTED_TO_HO = 'submitted_to_ho';
    const STATUS_SUBMITTED_TO_ADMIN = 'submitted_to_admin';
    const STATUS_SUBMITTED_TO_SUPERADMIN = 'submitted_to_superadmin';
    const STATUS_RETURNED_TO_RO = 'returned_to_ro';
    const STATUS_RETURNED_TO_HO = 'returned_to_ho';
    const STATUS_RETURNED_TO_ADMIN = 'returned_to_admin';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REOPENED = 'reopened';
    const STATUS_APPROVED = 'approved';
    const STATUS_SUBMITTED_TO_OUSEC = 'submitted_to_ousec';
    const STATUS_RETURNED_TO_OUSEC = 'returned_to_ousec';

    /**
     * All valid status values (for validation and reference).
     */
    const ALL_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED_TO_RO,
        self::STATUS_RETURNED_TO_PSTO,
        self::STATUS_RETURNED_TO_AGENCY,
        self::STATUS_SUBMITTED_TO_HO,
        self::STATUS_SUBMITTED_TO_ADMIN,
        self::STATUS_SUBMITTED_TO_SUPERADMIN,
        self::STATUS_RETURNED_TO_RO,
        self::STATUS_RETURNED_TO_HO,
        self::STATUS_RETURNED_TO_ADMIN,
        self::STATUS_REJECTED,
        self::STATUS_REOPENED,
        self::STATUS_APPROVED,
        self::STATUS_SUBMITTED_TO_OUSEC,
        self::STATUS_RETURNED_TO_OUSEC,
    ];

    // --- RELATIONSHIPS ---

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * Alias for submitter to allow $objective->user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Direct region relationship.
     * Named `regionRelation` to avoid collision with the `getRegionAttribute` accessor.
     */
    public function regionRelation(): BelongsTo
    {
        return $this->belongsTo(PhilippineRegion::class, 'region_id');
    }

    /**
     * Alias: region() calls regionRelation() for eager loading compatibility.
     */
    public function region(): BelongsTo
    {
        return $this->regionRelation();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Agency relationship (normalized FK).
     * Replaces text-based dost_agency/agency_code columns.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(DOSTAgency::class, 'agency_id');
    }

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(Pillar::class);
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(Outcome::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function mandatoryAssignments(): HasMany
    {
        return $this->hasMany(IndicatorMandatoryAssignment::class, 'objective_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(IndicatorHistory::class, 'objective_id')->latest();
    }

    public function rejectionNotes(): HasMany
    {
        return $this->hasMany(RejectionNote::class, 'objective_id');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(Proof::class, 'objective_id')->latest();
    }

    public function myRejectionNote()
    {
        return $this->hasOne(RejectionNote::class, 'objective_id')
            ->where('visible_to_user_id', auth()->id());
    }

    // --- ACCESSORS ---

    /**
     * Resolve region through multiple paths (with eager-loaded data, no extra queries).
     *
     * WHY THIS WAS BROKEN (Lazy Loading / N+1 Bug):
     * ─────────────────────────────────────────────
     * The old accessor called `$this->region()->exists()` and `$this->region()->first()`
     * which executed 2-3 extra SQL queries PER RECORD, even when the region was already
     * loaded via eager loading. If you loaded 100 objectives, that's 200-300 extra queries.
     *
     * HOW WE FIXED IT:
     * ─────────────────
     * 1. We renamed the BelongsTo relationship to `regionRelation()` so it doesn't
     *    clash with this accessor name.
     * 2. We added `regionRelation` to `protected $with = [...]` so it's always eager-loaded
     *    automatically by Laravel in a single query.
     * 3. This accessor now checks the already-loaded `regionRelation` attribute first
     *    (zero extra queries), then falls back to the office/submitter chain using
     *    only already-loaded relationships.
     *
     * RESULT: 0 extra queries instead of 2-3 per record = massive performance improvement.
     *
     * SIMPLE EXPLANATION FOR JUNIOR DEVS:
     * ────────────────────────────────────
     * Imagine you have a list of 50 indicators on a page. The old code asked the database
     * "hey, what region is this?" for EACH indicator separately — that's 50 extra trips
     * to the database just for regions! With eager loading, we say "give me ALL the
     * regions for these 50 indicators in ONE trip." That's what `protected $with` does.
     */
    public function getRegionAttribute()
    {
        // 1. Direct region via eager-loaded relationship (zero queries)
        if ($this->relationLoaded('regionRelation') && $this->regionRelation) {
            return $this->regionRelation;
        }

        // 2. Via Office (already eager-loaded via $with)
        if ($this->relationLoaded('office') && $this->office && $this->office->relationLoaded('region')) {
            return $this->office->region;
        }

        // 3. Via Submitter's Office (already eager-loaded via $with)
        if ($this->relationLoaded('submitter') && $this->submitter
            && $this->submitter->relationLoaded('office') && $this->submitter->office
            && $this->submitter->office->relationLoaded('region')) {
            return $this->submitter->office->region;
        }

        // 4. Fallback: load if not yet loaded (rare path — only happens outside eager loading)
        if ($this->region_id) {
            return $this->regionRelation;
        }

        return null;
    }

    // --- WORKFLOW METHODS ---

    /**
     * Record a history entry for this objective.
     */
    public function recordHistory(string $action, ?array $oldValues = null, ?array $newValues = null, ?string $note = null): void
    {
        try {
            $this->histories()->create([
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'rejection_note' => $note,
                'actor_user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record objective history', [
                'objective_id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alias for recordHistory — fixes Bug 1.2 where code calls addHistory() but method didn't exist.
     * Now both addHistory() and recordHistory() work.
     */
    public function addHistory(string $field, ?string $oldValue, ?string $newValue, ?string $note = null): void
    {
        $this->recordHistory($note ?? 'update', [$field => $oldValue], [$field => $newValue], $note);
    }

    public function submitToRO(): void
    {
        try {
            $oldStatus = $this->status;

            $this->recordHistory('submit_to_ro', [
                'status' => $this->status,
            ], [
                'status' => self::STATUS_SUBMITTED_TO_RO,
                'submitted_to_ro_at' => now()->toDateTimeString(),
            ]);

            $this->update([
                'status' => self::STATUS_SUBMITTED_TO_RO,
                'submitted_to_ro_at' => now(),
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_SUBMITTED_TO_RO);
            \App\Services\NotificationService::make()->notifyIndicatorSubmitted($this);
        } catch (\Throwable $e) {
            Log::error('Failed to submit objective to RO', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitToHO(): void
    {
        try {
            $submitter = $this->submitter;
            $oldStatus = $this->status;

            if ($submitter && $submitter->isAgency()) {
                $agency = $submitter->agency;
                if ($agency) {
                    $assignedHO = User::find($agency->head_user_id);
                    if ($assignedHO) {
                        \App\Services\NotificationService::make()->notifyAgencySubmissionToHO($this, $assignedHO);
                    }
                }
            }

            $this->recordHistory('submit_to_ho', [
                'status' => $this->status,
            ], [
                'status' => self::STATUS_SUBMITTED_TO_HO,
                'submitted_to_ho_at' => now()->toDateTimeString(),
            ]);

            $this->update([
                'status' => self::STATUS_SUBMITTED_TO_HO,
                'submitted_to_ho_at' => now(),
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_SUBMITTED_TO_HO);
        } catch (\Throwable $e) {
            Log::error('Failed to submit objective to HO', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitToRegionalHead(): void
    {
        try {
            $oldStatus = $this->status;

            $this->recordHistory('submit_to_regional_head', [
                'status' => $this->status,
            ], [
                'status' => self::STATUS_SUBMITTED_TO_RO,
                'submitted_to_ro_at' => now()->toDateTimeString(),
            ]);

            $this->update([
                'status' => self::STATUS_SUBMITTED_TO_RO,
                'submitted_to_ro_at' => now(),
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_SUBMITTED_TO_RO);
            \App\Services\NotificationService::make()->notifyIndicatorSubmitted($this);
        } catch (\Throwable $e) {
            Log::error('Failed to submit objective to Regional Head', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitToAdmin(): void
    {
        try {
            $oldStatus = $this->status;

            $this->recordHistory('submit_to_admin', [
                'status' => $this->status,
            ], [
                'status' => self::STATUS_SUBMITTED_TO_ADMIN,
                'submitted_to_admin_at' => now()->toDateTimeString(),
            ]);

            $this->update([
                'status' => self::STATUS_SUBMITTED_TO_ADMIN,
                'submitted_to_admin_at' => now(),
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_SUBMITTED_TO_ADMIN);
            \App\Services\NotificationService::make()->notifyIndicatorForwarded($this, 'Administrator');
        } catch (\Throwable $e) {
            Log::error('Failed to submit objective to Admin', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitToOUSEC(): void
    {
        try {
            $oldStatus = $this->status;

            $this->recordHistory('submit_to_ousec', [
                'status' => $this->status,
            ], [
                'status' => self::STATUS_SUBMITTED_TO_OUSEC,
                'submitted_to_ousec_at' => now()->toDateTimeString(),
            ]);

            $this->update([
                'status' => self::STATUS_SUBMITTED_TO_OUSEC,
                'submitted_to_ousec_at' => now(),
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_SUBMITTED_TO_OUSEC);
            \App\Services\NotificationService::make()->notifyIndicatorSubmittedToOUSEC($this);
        } catch (\Throwable $e) {
            Log::error('Failed to submit objective to OUSEC', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitToSuperAdmin(): void
    {
        try {
            $oldStatus = $this->status;

            $this->recordHistory('submit_to_superadmin', [
                'status' => $this->status,
            ], [
                'status' => self::STATUS_SUBMITTED_TO_SUPERADMIN,
                'submitted_to_superadmin_at' => now()->toDateTimeString(),
            ]);

            $this->update([
                'status' => self::STATUS_SUBMITTED_TO_SUPERADMIN,
                'submitted_to_superadmin_at' => now(),
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_SUBMITTED_TO_SUPERADMIN);
            \App\Services\NotificationService::make()->notifyIndicatorForwarded($this, 'SuperAdmin');
        } catch (\Throwable $e) {
            Log::error('Failed to submit objective to SuperAdmin', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function approve(User $approver): void
    {
        try {
            if ($approver->isSA()) {
                $oldStatus = $this->status;

                $this->recordHistory('approve', [
                    'status' => $this->status,
                ], [
                    'status' => self::STATUS_APPROVED,
                    'approved_at' => now()->toDateTimeString(),
                    'approved_by' => $approver->id,
                    'is_locked' => true,
                ]);

                $this->update([
                    'status' => self::STATUS_APPROVED,
                    'is_locked' => true,
                    'approved_at' => now(),
                    'approved_by' => $approver->id,
                    'rejection_reason' => null,
                ]);

                \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, self::STATUS_APPROVED);
                \App\Services\NotificationService::make()->notifyIndicatorApproved($this);
            } else {
                if ($approver->canActAsHeadOfOffice()) {
                    if ($approver->office && $approver->office->type === 'PSTO' && $approver->office->head_user_id === $approver->id) {
                        $this->submitToRegionalHead();
                    } elseif ($this->submitter && $this->submitter->isAgency()) {
                        $this->submitToOUSEC();
                    } else {
                        $this->submitToOUSEC();
                    }
                } elseif ($approver->isOUSEC()) {
                    $this->submitToAdmin();
                } elseif ($approver->isAdministrator()) {
                    $this->submitToSuperAdmin();
                } elseif ($approver->isRO()) {
                    $this->submitToHO();
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to approve/forward objective', [
                'objective_id' => $this->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function reject(User $rejector, ?string $reason = null): void
    {
        try {
            $oldStatus = $this->status;
            $newStatus = 'rejected';
            $recipientId = null;

            if ($rejector->isSA() || $rejector->isAdministrator()) {
                if ($this->submitter && $this->submitter->agency && $this->submitter->agency->code === AgencyConstants::DOST_CO) {
                    $newStatus = self::STATUS_RETURNED_TO_HO;
                    $recipientId = $this->determineHORecipientForReturn();
                } else {
                    $newStatus = self::STATUS_RETURNED_TO_OUSEC;
                    $recipientId = $this->determineOUSECRecipientForReturn();
                }
            } elseif ($rejector->isOUSEC()) {
                $newStatus = self::STATUS_RETURNED_TO_HO;
                $recipientId = $this->determineHORecipientForReturn();
            } elseif ($rejector->canActAsHeadOfOffice()) {
                if ($this->submitter && $this->submitter->isAgency()) {
                    $newStatus = self::STATUS_RETURNED_TO_AGENCY;
                    // BUG FIX 2.4: Was `$this->submitter_id` — column doesn't exist; correct column is `submitted_by_user_id`
                    $recipientId = $this->submitted_by_user_id;
                } else {
                    $newStatus = self::STATUS_RETURNED_TO_PSTO;
                    $recipientId = $this->determineOriginalMakerId();
                }
            } elseif ($rejector->isRO()) {
                $newStatus = self::STATUS_RETURNED_TO_PSTO;
                $recipientId = $this->submitted_by_user_id;
                $this->notifyHOAboutRejection($rejector, $reason);
            }

            if ($reason && $recipientId) {
                RejectionNote::create([
                    'objective_id' => $this->id,
                    'rejected_by_user_id' => $rejector->id,
                    'visible_to_user_id' => $recipientId,
                    'note' => $reason,
                ]);
            }

            $this->recordHistory('reject', [
                'status' => $this->status,
            ], [
                'status' => $newStatus,
                'is_locked' => false,
            ], $reason);

            $this->update([
                'status' => $newStatus,
                'is_locked' => false,
                'rejected_at' => now(),
                'rejected_by' => $rejector->id,
                'approved_at' => null,
                'approved_by' => null,
            ]);

            \App\Services\AuditService::logWorkflowTransition($this, $oldStatus, $newStatus);

            if ($recipientId) {
                $recipient = User::find($recipientId);
                if ($recipient) {
                    \App\Services\NotificationService::make()->notifyIndicatorRejected($this, $reason, $recipient);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to reject objective', [
                'objective_id' => $this->id,
                'rejector_id' => $rejector->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function determineHORecipientForReturn(): ?int
    {
        $submitter = $this->submitter;

        if ($submitter && $submitter->agency_id) {
            $agency = DOSTAgency::find($submitter->agency_id);
            if ($agency && $agency->head_user_id) {
                return $agency->head_user_id;
            }
        }

        if ($submitter && $submitter->office_id) {
            $office = Office::find($submitter->office_id);
            if ($office && $office->head_user_id) {
                return $office->head_user_id;
            }
        }

        if ($submitter && $submitter->region_id) {
            $ho = User::where('region_id', $submitter->region_id)
                ->where('role', User::ROLE_HO)
                ->first();
            if ($ho) {
                return $ho->id;
            }
        }

        return $this->submitted_by_user_id ?? null;
    }

    private function determineOUSECRecipientForReturn(): ?int
    {
        $submitter = $this->submitter;
        if (!$submitter) {
            return null;
        }

        if ($submitter->office_id || $submitter->region_id) {
            $ousec = User::where('role', User::ROLE_OUSEC_RO)->first();
            if ($ousec) {
                return $ousec->id;
            }
        }

        if ($submitter->agency_id) {
            $agency = DOSTAgency::find($submitter->agency_id);
            if ($agency) {
                if (AgencyConstants::isOUSECSTSCluster($agency->cluster)) {
                    $ousec = User::where('role', User::ROLE_OUSEC_STS)->first();
                    if ($ousec) {
                        return $ousec->id;
                    }
                }
                if (AgencyConstants::isOUSECRDCluster($agency->cluster)) {
                    $ousec = User::where('role', User::ROLE_OUSEC_RD)->first();
                    if ($ousec) {
                        return $ousec->id;
                    }
                }
            }
        }

        return null;
    }

    private function determineOriginalMakerId(): ?int
    {
        return $this->submitted_by_user_id;
    }

    private function notifyHOAboutRejection(User $ro, ?string $reason): void
    {
        try {
            $hoId = $this->determineHORecipientForReturn();
            if ($hoId) {
                $ho = User::find($hoId);
                if ($ho) {
                    \App\Services\NotificationService::make()->notifyHOAboutRejection($this, $ro, $reason, $ho);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify HO about rejection', [
                'objective_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendDownToMaker(User $ho, string $hoNote): bool
    {
        try {
            if (!$ho->canActAsHeadOfOffice() || $this->status !== self::STATUS_RETURNED_TO_HO) {
                return false;
            }

            $newStatus = null;
            $makerId = null;

            if ($this->submitter && $this->submitter->isAgency()) {
                $newStatus = self::STATUS_RETURNED_TO_AGENCY;
                $makerId = $this->submitted_by_user_id;
            } else {
                $newStatus = self::STATUS_RETURNED_TO_PSTO;
                $makerId = $this->determineOriginalMakerId();
            }

            if (!$newStatus || !$makerId) {
                return false;
            }

            RejectionNote::create([
                'objective_id' => $this->id,
                'rejected_by_user_id' => $ho->id,
                'visible_to_user_id' => $makerId,
                'note' => $hoNote,
            ]);

            $this->recordHistory('send_down_to_maker', [
                'status' => $this->status,
            ], [
                'status' => $newStatus,
            ], $hoNote);

            $this->update([
                'status' => $newStatus,
                'is_locked' => false,
            ]);

            $maker = User::find($makerId);
            if ($maker) {
                \App\Services\NotificationService::make()->notifyIndicatorRejected($this, $hoNote, $maker);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send objective down to maker', [
                'objective_id' => $this->id,
                'ho_id' => $ho->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function forward(User $user): bool
    {
        try {
            if (!str_starts_with($this->status, 'returned_to_')) {
                return false;
            }

            $nextStatus = match ($this->status) {
                self::STATUS_RETURNED_TO_AGENCY => match (true) {
                    $user->isAgency() => self::STATUS_SUBMITTED_TO_HO,
                    default => false,
                },
                self::STATUS_RETURNED_TO_PSTO => match (true) {
                    $user->isPsto() => self::STATUS_SUBMITTED_TO_RO,
                    default => false,
                },
                // HO forwards back to OUSEC (follows the approve path, not straight to Admin)
                self::STATUS_RETURNED_TO_HO => match (true) {
                    $user->canActAsHeadOfOffice() => self::STATUS_SUBMITTED_TO_OUSEC,
                    default => false,
                },
                self::STATUS_RETURNED_TO_RO => match (true) {
                    $user->isRO() => self::STATUS_SUBMITTED_TO_HO,
                    default => false,
                },
                self::STATUS_RETURNED_TO_OUSEC => match (true) {
                    $user->isOUSEC() => self::STATUS_SUBMITTED_TO_ADMIN,
                    default => false,
                },
                self::STATUS_RETURNED_TO_ADMIN => match (true) {
                    $user->isAdministrator() => self::STATUS_SUBMITTED_TO_SUPERADMIN,
                    default => false,
                },
                default => false,
            };

            if (!$nextStatus) {
                return false;
            }

            $this->update([
                'status' => $nextStatus,
                'is_locked' => true,
            ]);

            $this->recordHistory('forward', [
                'status' => $this->status,
            ], [
                'status' => $nextStatus,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to forward objective', [
                'objective_id' => $this->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // --- SCOPES ---

    /**
     * Scope: Authorized indicator visibility.
     *
     * This is the CANONICAL visibility method. All dashboard queries MUST use this scope.
     *
     * Visibility rules:
     * - SA / Admin / Execom: see everything
     * - OUSEC-RO: regional-flow indicators at OUSEC level or above
     * - OUSEC-STS/RD: agency-flow indicators from their assigned clusters at OUSEC level or above
     * - Agency users (staff or HO): own indicators + all indicators from same agency
     * - PSTO staff (non-head): own indicators only
     * - PSTO Head: own indicators + all indicators from their PSTO office
     * - RO / RO Head: own indicators + all indicators from their RO office + child PSTOs
     *   (uses user.office_id, NOT head_user_id lookup, so new accounts work immediately)
     * - Unknown/unmatched: own indicators only (safe fallback)
     */
    public function scopeAuthorized(Builder $query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // --- Global access roles ---
        if ($user->isSA() || $user->isAdministrator() || $user->isExecom()) {
            return $query;
        }

        // --- OUSEC roles (see ALL indicators in their scope, regardless of status) ---
        if ($user->isOUSEC()) {
            return $query->where(function ($q) use ($user) {
                if ($user->isOUSEROR()) {
                    // OUSEC-RO: all regional-flow indicators (any status)
                    $q->whereNotNull('office_id');
                } else {
                    // OUSEC-STS / OUSEC-RD: all agency-flow indicators from assigned clusters (any status)
                    $allowedClusters = $user->getOUSECClusters();
                    if (!empty($allowedClusters)) {
                        $q->where(function ($sub) use ($allowedClusters) {
                            $sub->whereHas('submitter.agency', function ($aq) use ($allowedClusters) {
                                $aq->whereIn('cluster', $allowedClusters);
                            })
                            ->orWhereHas('agency', function ($aq) use ($allowedClusters) {
                                $aq->whereIn('cluster', $allowedClusters);
                            });
                        });
                    } else {
                        // No clusters assigned — see nothing
                        $q->whereRaw('1 = 0');
                    }
                }
            });
        }

        // --- Agency-flow users (user has agency_id) ---
        if ($user->agency_id) {
            return $query->where(function ($q) use ($user) {
                // Own indicators (any status)
                $q->where('submitted_by_user_id', $user->id);
                // All indicators from the same agency (via direct column or submitter relationship)
                $q->orWhere('agency_id', $user->agency_id);
                $q->orWhereHas('submitter', function ($s) use ($user) {
                    $s->where('agency_id', $user->agency_id);
                });
            });
        }

        // --- Regional-flow users (office-based) ---
        return $query->where(function ($q) use ($user) {
            // ALWAYS include own indicators as baseline (safe fallback)
            $q->where('submitted_by_user_id', $user->id);

            $office = $user->office;

            if (!$user->office_id || !$office) {
                // No office assigned — only own indicators (already covered above)
                return;
            }

            if ($office->type === 'PSTO') {
                // ALL PSTO users (staff + head) see all indicators from their office
                // This ensures SA-imported indicators assigned to this office are visible
                $q->orWhere('office_id', $user->office_id);

            } elseif ($office->type === 'RO') {
                // RO user (any role — ro, head_officer, etc.): see their RO + child PSTO indicators
                $childPstoIds = Office::where('parent_office_id', $user->office_id)
                    ->pluck('id')
                    ->all();
                $allOfficeIds = array_merge([$user->office_id], $childPstoIds);

                $q->orWhereIn('office_id', $allOfficeIds);
            }
            // CO/HO/other office types: only own indicators (already covered above)
        });
    }

    public function scopeForRegion($query, $regionId)
    {
        return $query->where(function ($q) use ($regionId) {
            $q->where('region_id', $regionId)
                ->orWhereHas('office', function ($sub) use ($regionId) {
                    $sub->where('region_id', $regionId);
                })
                ->orWhereHas('submitter.office', function ($sub) use ($regionId) {
                    $sub->where('region_id', $regionId);
                });
        });
    }

    public function scopeByCategory($query, ?string $category)
    {
        return $category ? $query->where('category', $category) : $query;
    }

    public function scopeByYear($query, ?int $year)
    {
        if (!$year) {
            return $query;
        }

        return $query->where(function ($q) use ($year) {
            $q->where('target_period', $year)
                ->orWhere(function ($sub) use ($year) {
                    $sub->where('target_period', 'LIKE', '%-%')
                        ->whereRaw("CAST(SUBSTR(target_period, 1, INSTR(target_period, '-') - 1) AS SIGNED) <= ?", [$year])
                        ->whereRaw("CAST(SUBSTR(target_period, INSTR(target_period, '-') + 1) AS SIGNED) >= ?", [$year]);
                });
        });
    }

    public function scopeByStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('indicator', 'like', '%' . $search . '%')
                ->orWhere('objective_result', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    public function scopeMandatory($query, ?bool $mandatory = null)
    {
        return $mandatory !== null ? $query->where('is_mandatory', $mandatory) : $query;
    }
}
