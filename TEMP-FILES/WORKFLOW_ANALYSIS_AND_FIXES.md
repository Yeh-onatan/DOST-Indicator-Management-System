# Workflow Analysis & Required Fixes

**Date:** February 14, 2026  
**Scope:** Complete workflow implementation review for DOST Indicator Management System

---

## 🎯 Required Workflow (Your Specification)

### Creation Rights
**Who can CREATE indicators:**
- ✅ PSTO
- ✅ RO (Regional Office)
- ✅ AGENCY
- ✅ SUPER ADMIN

### PSTO/RO Flow
```
PSTO creates draft
    ↓
PSTO submits → RO
    ↓
RO reviews (can reject back to PSTO)
    ↓
RO approves → HO
    ↓
HO reviews (can reject back to RO)
    ↓
HO approves → OUSEC-RO
    ↓
OUSEC-RO approves → ADMIN
    ↓
ADMIN approves → SUPER ADMIN
    ↓
SUPER ADMIN approves → APPROVED (final)
```

### AGENCY Flow
```
AGENCY creates draft
    ↓
AGENCY submits → AGENCY HO (specific to that agency)
    ↓
AGENCY HO reviews (can reject back to AGENCY)
    ↓
AGENCY HO approves → OUSEC-STS or OUSEC-RD (based on agency cluster)
    ↓
OUSEC approves → ADMIN
    ↓
ADMIN approves → SUPER ADMIN
    ↓
SUPER ADMIN approves → APPROVED (final)
```

### OUSEC Routing Rules
**OUSEC-STS receives from agencies:**
- NAST, NRCP (collegial cluster)
- PAGASA, PHIVOLCS, PSHS, SEI, STII, TAPI (ssi cluster)
- DOST-CO (main cluster)

**OUSEC-RD receives from agencies:**
- PCAARRD, PCHRD, PCIEERD (council cluster)
- ASTI, FNRI, FPRDI, ITDI, MIRDC, PNRI, PTRI (rdi cluster)

**OUSEC-RO receives from:**
- Regional Offices (RO)
- Provincial Science and Technology Offices (PSTO)

### Visibility Rules
- **PSTO** - sees only own drafts
- **RO** - sees only PSTO drafts from their region
- **AGENCY** - sees only own agency's drafts
- **AGENCY HO** - sees only drafts from their specific agency
- **HO** - sees only RO drafts from their region
- **OUSEC-RO** - sees only RO/PSTO flow indicators
- **OUSEC-STS/RD** - sees only agency indicators from their assigned clusters
- **ADMIN** - sees ALL drafts
- **SUPER ADMIN** - sees ALL drafts

### Edit Permissions
- **RO can only edit drafts RO created, NOT PSTO's drafts**
- Each role can only edit their OWN drafts
- Exception: ADMIN and SUPER ADMIN can edit anything

---

## 🔍 Current Implementation Analysis

### ✅ What's Working Correctly

#### 1. Agency Cluster Routing (OUSEC-STS vs OUSEC-RD)
**File:** `app/Constants/AgencyConstants.php`

```php
// CORRECT: OUSEC-STS clusters
public const OUSEC_STS_CLUSTERS = [
    self::CLUSTER_SSI,        // PAGASA, PHIVOLCS, PSHS, SEI, STII, TAPI, DOST-CO
    self::CLUSTER_COLLEGIAL,  // NAST, NRCP
    self::CLUSTER_MAIN,       // Main DOST
];

// CORRECT: OUSEC-RD clusters
public const OUSEC_RD_CLUSTERS = [
    self::CLUSTER_COUNCIL,    // PCAARRD, PCHRD, PCIEERD
    self::CLUSTER_RDI,        // ASTI, FNRI, FPRDI, ITDI, MIRDC, PNRI, PTRI
];
```

**Status:** ✅ **CORRECT** - Matches your specification exactly.

#### 2. Database Seeder (Agency Clusters)
**File:** `database/seeders/AgenciesSeeder.php`

All agencies are correctly assigned to clusters:
- ✅ NAST, NRCP → `collegial`
- ✅ PAGASA, PHIVOLCS, PSHS, SEI, STII, TAPI → `ssi`
- ✅ PCAARRD, PCHRD, PCIEERD → `council`
- ✅ ASTI, FNRI, FPRDI, ITDI, MIRDC, PNRI, PTRI → `rdi`

**Status:** ✅ **CORRECT**

---

## 🚨 Critical Issues Found

### ISSUE #1: Creation Rights - Too Restrictive
**Location:** `app/Livewire/Dashboard/UnifiedDashboard.php`

**Current Code (Lines 1819, 2409):**
```php
if (!Auth::user()->isPSTO() && !Auth::user()->isAgency()) {
    $this->dispatch('toast', message: 'Only PSTO and Agency accounts can create indicators.', type: 'error');
    return;
}
```

**Problem:** 
- ❌ RO cannot create indicators (required in spec)
- ❌ SUPER ADMIN cannot create indicators (required in spec)

**Required Fix:**
```php
if (!Auth::user()->isPSTO() 
    && !Auth::user()->isAgency() 
    && !Auth::user()->isRO() 
    && !Auth::user()->isSuperAdmin()) {
    $this->dispatch('toast', message: 'You do not have permission to create indicators.', type: 'error');
    return;
}
```

**Impact:** HIGH - Blocks RO and Super Admin from creating indicators.

---

### ISSUE #2: HO vs AGENCY HO Confusion
**Location:** `app/Models/Objective.php` - Lines 380-430 (submitToHO method)

**Current Logic Problem:**
The system does NOT distinguish between:
1. **HO** (Head Office for PSTO/RO flow) - should route to OUSEC-RO
2. **AGENCY HO** (Head of a specific agency) - should route to OUSEC-STS or OUSEC-RD

**Current Code (Line 556-571 in approve() method):**
```php
if ($approver->canActAsHeadOfOffice()) {
    if ($approver->office && $approver->office->type === 'PSTO' && ...) {
        $this->submitToRegionalHead();
    } elseif ($this->submitter && $this->submitter->agency && $this->submitter->agency->code === AgencyConstants::DOST_CO) {
        $this->submitToAdmin(); // ❌ WRONG: DOST-CO should go to OUSEC-STS
    } elseif ($this->submitter && $this->submitter->isAgency()) {
        $this->submitToOUSEC(); // ✅ Correct for agencies
    } else {
        $this->submitToOUSEC(); // ❌ WRONG: RO/PSTO should go to OUSEC-RO specifically
    }
}
```

**Problem:**
1. ❌ DOST-CO skips OUSEC and goes directly to ADMIN
2. ❌ OUSEC-RO routing is not explicitly enforced for RO/PSTO flow
3. ❌ No distinction between AGENCY HO and regular HO roles

**Required Fix:** See detailed implementation plan below.

---

### ISSUE #3: RO Can Edit PSTO Drafts
**Location:** `app/Livewire/Dashboard/UnifiedDashboard.php` - Lines 2268-2290

**Current Code:**
```php
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
            // Blocks editing
        }
    }
}
```

**Problem:** 
❌ RO can edit ANY draft from PSTO offices in their region, violating the rule: "RO can only edit drafts RO made, not PSTO's"

**Required Fix:**
```php
} elseif ($user->isRO()) {
    // RO can ONLY edit their own drafts, not PSTO drafts
    if ($objective->submitted_by_user_id !== $user->id) {
        $this->dispatch('toast', message: 'RO can only edit indicators they created.', type: 'error');
        return;
    }
}
```

**Impact:** HIGH - RO has unauthorized editing access to PSTO drafts.

---

### ISSUE #4: Visibility Scope - RO Sees Too Much
**Location:** `app/Models/Objective.php` - Lines 920-945 (scopeAuthorized)

**Current Code:**
```php
} elseif ($user->isRO()) {
    $q->orWhere('submitted_by_user_id', $user->id); // ✅ Own drafts

    $roOffices = Office::where('head_user_id', $user->id)
        ->where('type', 'RO')
        ->pluck('id');

    if ($roOffices->isNotEmpty()) {
        $childPstoOfficeIds = Office::whereIn('parent_office_id', $roOffices)
            ->pluck('id');
        $allOfficeIds = $roOffices->concat($childPstoOfficeIds)->unique();

        $q->orWhere(function ($sub) use ($allOfficeIds) {
            $sub->whereIn('office_id', $allOfficeIds)
                ->whereIn('status', [
                    self::STATUS_SUBMITTED_TO_RO,
                    self::STATUS_RETURNED_TO_RO,
                    self::STATUS_SUBMITTED_TO_HO,
                    self::STATUS_SUBMITTED_TO_ADMIN,
                    self::STATUS_APPROVED,
                ]);
        });
    }
}
```

**Analysis:**
- ✅ RO sees their own drafts
- ✅ RO sees PSTO drafts that are `submitted_to_ro` (correct - they need to review these)
- ✅ RO sees drafts in later stages (submitted_to_ho, approved, etc.) for tracking

**Status:** ✅ **MOSTLY CORRECT** - This is actually appropriate. RO needs to see PSTO submissions to review them.

**BUT:** The issue is RO should only see PSTO drafts when status = `submitted_to_ro`. Currently sees ALL statuses for child offices.

**Suggested Refinement:**
```php
$q->orWhere(function ($sub) use ($allOfficeIds) {
    $sub->whereIn('office_id', $allOfficeIds)
        ->where(function($statusSub) {
            $statusSub->where('status', self::STATUS_SUBMITTED_TO_RO)
                      ->orWhere('status', self::STATUS_RETURNED_TO_RO);
        });
});

// Separately, RO can see indicators they approved (now in later stages)
$q->orWhere(function ($sub) use ($user) {
    $sub->where('approved_by', $user->id)
        ->whereIn('status', [
            self::STATUS_SUBMITTED_TO_HO,
            self::STATUS_SUBMITTED_TO_ADMIN,
            self::STATUS_APPROVED,
        ]);
});
```

---

### ISSUE #5: AGENCY HO Visibility Not Implemented
**Location:** `app/Models/Objective.php` - scopeAuthorized

**Problem:** 
There is NO specific visibility logic for users who are:
- Role: `head_officer` (HO)
- AND assigned to a specific agency (`agency_id` is set)
- These users should only see drafts from THEIR agency

**Current Code (Lines 956-990):**
```php
} elseif ($user->canActAsHeadOfOffice()) {
    if ($user->agency_id) {
        $q->orWhere(function ($sub) use ($user) {
            $sub->whereHas('submitter', function ($s) use ($user) {
                $s->where('agency_id', $user->agency_id);
            })
            ->whereIn('status', [
                self::STATUS_SUBMITTED_TO_HO,
                self::STATUS_RETURNED_TO_AGENCY,
                self::STATUS_SUBMITTED_TO_ADMIN, // ❌ WRONG: Agency HO should not see admin stage
                self::STATUS_APPROVED,
            ]);
        });
    }
    // ... more logic for region-based HO, office-based HO
}
```

**Issues:**
1. ✅ Correctly filters by agency_id
2. ❌ Allows AGENCY HO to see `submitted_to_admin` status (should only see up to `submitted_to_ousec`)
3. ❌ Missing `submitted_to_ousec` status in the list

**Required Fix:**
```php
if ($user->agency_id) {
    $q->orWhere(function ($sub) use ($user) {
        $sub->whereHas('submitter', function ($s) use ($user) {
            $s->where('agency_id', $user->agency_id);
        })
        ->whereIn('status', [
            self::STATUS_SUBMITTED_TO_HO,
            self::STATUS_RETURNED_TO_AGENCY,
            self::STATUS_SUBMITTED_TO_OUSEC,
            self::STATUS_APPROVED,
        ]);
    });
}
```

---

### ISSUE #6: PSTO Visibility - Can See Too Much
**Location:** `app/Models/Objective.php` - Lines 906-915

**Current Code:**
```php
} elseif ($user->isPSTO() && !$user->canActAsHeadOfOffice()) {
    $q->orWhere(function ($sub) use ($user) {
        $sub->where('office_id', $user->office_id)
            ->orWhere('submitted_by_user_id', $user->id);
    });
}
```

**Problem:**
❌ PSTO sees ALL drafts from their office (`where('office_id', $user->office_id)`), including drafts made by OTHER PSTO users in the same office.

**Specification:** "PSTO sees only own drafts"

**Required Fix:**
```php
} elseif ($user->isPSTO() && !$user->canActAsHeadOfOffice()) {
    // PSTO can ONLY see their own drafts
    $q->orWhere('submitted_by_user_id', $user->id);
}
```

**Impact:** MEDIUM - PSTO users can see each other's drafts within the same office.

---

### ISSUE #7: Missing STATUS_SUBMITTED_TO_SUPERADMIN Constant
**Location:** `app/Models/Objective.php`

**Current Status Constants (Lines 105-121):**
```php
public const STATUS_DRAFT = 'draft';
public const STATUS_SUBMITTED_TO_RO = 'submitted_to_ro';
public const STATUS_SUBMITTED_TO_HO = 'submitted_to_ho';
public const STATUS_SUBMITTED_TO_OUSEC = 'submitted_to_ousec';
public const STATUS_SUBMITTED_TO_ADMIN = 'submitted_to_admin';
public const STATUS_APPROVED = 'approved';
public const STATUS_REJECTED = 'rejected';
public const STATUS_RETURNED_TO_PSTO = 'returned_to_psto';
public const STATUS_RETURNED_TO_AGENCY = 'returned_to_agency';
public const STATUS_RETURNED_TO_RO = 'returned_to_ro';
public const STATUS_RETURNED_TO_HO = 'returned_to_ho';
public const STATUS_RETURNED_TO_OUSEC = 'returned_to_ousec';
public const STATUS_RETURNED_TO_ADMIN = 'returned_to_admin';
```

**Problem:**
❌ Missing `STATUS_SUBMITTED_TO_SUPERADMIN` constant (but there's a `submitToSuperAdmin()` method at Line 507)

**Required Fix:**
```php
public const STATUS_SUBMITTED_TO_SUPERADMIN = 'submitted_to_superadmin';
```

Add this constant, and ensure the database migration allows this status value.

---

## 📋 Complete Fix Implementation Plan

### Fix #1: Update Creation Permissions

**File:** `app/Livewire/Dashboard/UnifiedDashboard.php`

**Locations to change:**
1. Line 1819 (in `openCreate()` method)
2. Line 2409 (in `saveQuickForm()` method)
3. Line 1826 (in `openQuickForm()` method)

**Change from:**
```php
if (!Auth::user()->isPSTO() && !Auth::user()->isAgency()) {
```

**Change to:**
```php
if (!Auth::user()->isPSTO() 
    && !Auth::user()->isAgency() 
    && !Auth::user()->isRO() 
    && !Auth::user()->isSuperAdmin()) {
```

---

### Fix #2: Add Missing Status Constant

**File:** `app/Models/Objective.php`

**Location:** After line 112 (with other STATUS constants)

**Add:**
```php
public const STATUS_SUBMITTED_TO_SUPERADMIN = 'submitted_to_superadmin';
```

---

### Fix #3: Fix PSTO Visibility (Only Own Drafts)

**File:** `app/Models/Objective.php`

**Location:** Lines 906-915 in `scopeAuthorized()` method

**Change from:**
```php
} elseif ($user->isPSTO() && !$user->canActAsHeadOfOffice()) {
    $q->orWhere(function ($sub) use ($user) {
        $sub->where('office_id', $user->office_id)
            ->orWhere('submitted_by_user_id', $user->id);
    });
}
```

**Change to:**
```php
} elseif ($user->isPSTO() && !$user->canActAsHeadOfOffice()) {
    // PSTO can ONLY see their own drafts
    $q->orWhere('submitted_by_user_id', $user->id);
}
```

---

### Fix #4: Fix RO Edit Permissions (Only Own Drafts)

**File:** `app/Livewire/Dashboard/UnifiedDashboard.php`

**Location:** Lines 2268-2290 in `openEdit()` method

**Change from:**
```php
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
```

**Change to:**
```php
} elseif ($user->isRO()) {
    // RO can ONLY edit their own drafts, not PSTO drafts
    if ($objective->submitted_by_user_id !== $user->id) {
        $this->dispatch('toast', message: 'RO can only edit indicators they created, not PSTO drafts.', type: 'error');
        return;
    }
}
```

---

### Fix #5: Fix AGENCY HO Visibility Statuses

**File:** `app/Models/Objective.php`

**Location:** Lines 956-970 in `scopeAuthorized()` method

**Change from:**
```php
if ($user->agency_id) {
    $q->orWhere(function ($sub) use ($user) {
        $sub->whereHas('submitter', function ($s) use ($user) {
            $s->where('agency_id', $user->agency_id);
        })
        ->whereIn('status', [
            self::STATUS_SUBMITTED_TO_HO,
            self::STATUS_RETURNED_TO_AGENCY,
            self::STATUS_SUBMITTED_TO_ADMIN,
            self::STATUS_APPROVED,
        ]);
    });
}
```

**Change to:**
```php
if ($user->agency_id) {
    $q->orWhere(function ($sub) use ($user) {
        $sub->whereHas('submitter', function ($s) use ($user) {
            $s->where('agency_id', $user->agency_id);
        })
        ->whereIn('status', [
            self::STATUS_SUBMITTED_TO_HO,
            self::STATUS_RETURNED_TO_AGENCY,
            self::STATUS_SUBMITTED_TO_OUSEC,
            self::STATUS_APPROVED,
        ]);
    });
}
```

---

### Fix #6: Fix OUSEC Routing in approve() Method

**File:** `app/Models/Objective.php`

**Location:** Lines 556-571 in `approve()` method

**Change from:**
```php
if ($approver->canActAsHeadOfOffice()) {
    if ($approver->office && $approver->office->type === 'PSTO' && $approver->office->head_user_id === $approver->id) {
        $this->submitToRegionalHead();
    } elseif ($this->submitter && $this->submitter->agency && $this->submitter->agency->code === AgencyConstants::DOST_CO) {
        $this->submitToAdmin();
    } elseif ($this->submitter && $this->submitter->isAgency()) {
        $this->submitToOUSEC();
    } else {
        $this->submitToOUSEC();
    }
}
```

**Change to:**
```php
if ($approver->canActAsHeadOfOffice()) {
    // PSTO Head forwards to Regional Head
    if ($approver->office && $approver->office->type === 'PSTO' && $approver->office->head_user_id === $approver->id) {
        $this->submitToRegionalHead();
    } 
    // Agency HO forwards to OUSEC (cluster-based routing)
    elseif ($this->submitter && $this->submitter->isAgency()) {
        $this->submitToOUSEC(); // Will route to OUSEC-STS or OUSEC-RD based on cluster
    } 
    // Regular HO (from RO/PSTO flow) forwards to OUSEC-RO
    else {
        $this->submitToOUSEC(); // Will route to OUSEC-RO for regional flow
    }
}
```

**Note:** The `submitToOUSEC()` method needs enhancement to handle OUSEC-RO routing explicitly.

---

### Fix #7: Enhance submitToOUSEC() Method for Explicit OUSEC-RO Routing

**File:** `app/Models/Objective.php`

**Location:** Lines 474-505 (submitToOUSEC method)

**Current Code:**
```php
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
```

**Required Enhancement:**
Add notification logic that determines the correct OUSEC type (STS, RD, or RO) and notifies the appropriate users.

**Enhanced Code:**
```php
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
        
        // Determine which OUSEC type should receive notification
        $ousecType = $this->determineOUSECType();
        \App\Services\NotificationService::make()->notifyIndicatorSubmittedToOUSEC($this, $ousecType);
    } catch (\Throwable $e) {
        Log::error('Failed to submit objective to OUSEC', [
            'objective_id' => $this->id,
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}

/**
 * Determine which OUSEC type should handle this indicator
 * 
 * @return string 'ousec_ro', 'ousec_sts', or 'ousec_rd'
 */
private function determineOUSECType(): string
{
    $submitter = $this->submitter;
    
    // If submitter is from an agency, use agency cluster
    if ($submitter && $submitter->agency_id) {
        $agency = $submitter->agency;
        if ($agency) {
            if (AgencyConstants::isOUSECSTSCluster($agency->cluster)) {
                return 'ousec_sts';
            }
            if (AgencyConstants::isOUSECRDCluster($agency->cluster)) {
                return 'ousec_rd';
            }
        }
    }
    
    // If submitter is from an office/region (PSTO/RO flow), use OUSEC-RO
    if ($submitter && ($submitter->office_id || $submitter->region_id)) {
        return 'ousec_ro';
    }
    
    // Default fallback to OUSEC-RO for regional flow
    return 'ousec_ro';
}
```

---

### Fix #8: Refine RO Visibility Scope

**File:** `app/Models/Objective.php`

**Location:** Lines 920-945 in `scopeAuthorized()` method

**Change from:**
```php
} elseif ($user->isRO()) {
    $q->orWhere('submitted_by_user_id', $user->id);

    $roOffices = Office::where('head_user_id', $user->id)
        ->where('type', 'RO')
        ->pluck('id');

    if ($roOffices->isNotEmpty()) {
        $childPstoOfficeIds = Office::whereIn('parent_office_id', $roOffices)
            ->pluck('id');
        $allOfficeIds = $roOffices->concat($childPstoOfficeIds)->unique();

        $q->orWhere(function ($sub) use ($allOfficeIds) {
            $sub->whereIn('office_id', $allOfficeIds)
                ->whereIn('status', [
                    self::STATUS_SUBMITTED_TO_RO,
                    self::STATUS_RETURNED_TO_RO,
                    self::STATUS_SUBMITTED_TO_HO,
                    self::STATUS_SUBMITTED_TO_ADMIN,
                    self::STATUS_APPROVED,
                ]);
        });
    }
}
```

**Change to:**
```php
} elseif ($user->isRO()) {
    // RO sees their own drafts
    $q->orWhere('submitted_by_user_id', $user->id);

    $roOffices = Office::where('head_user_id', $user->id)
        ->where('type', 'RO')
        ->pluck('id');

    if ($roOffices->isNotEmpty()) {
        $childPstoOfficeIds = Office::whereIn('parent_office_id', $roOffices)
            ->pluck('id');
        $allOfficeIds = $roOffices->concat($childPstoOfficeIds)->unique();

        // RO sees PSTO drafts only when submitted for RO review
        $q->orWhere(function ($sub) use ($allOfficeIds) {
            $sub->whereIn('office_id', $allOfficeIds)
                ->whereIn('status', [
                    self::STATUS_SUBMITTED_TO_RO,
                    self::STATUS_RETURNED_TO_RO,
                ]);
        });
        
        // RO can also see indicators they approved (now in later stages)
        $q->orWhere(function ($sub) use ($user) {
            $sub->where('approved_by', $user->id)
                ->whereIn('status', [
                    self::STATUS_SUBMITTED_TO_HO,
                    self::STATUS_SUBMITTED_TO_OUSEC,
                    self::STATUS_SUBMITTED_TO_ADMIN,
                    self::STATUS_SUBMITTED_TO_SUPERADMIN,
                    self::STATUS_APPROVED,
                ]);
        });
    }
}
```

---

## 🔄 Workflow Routing Summary (After Fixes)

### PSTO/RO Flow
```
PSTO creates → status: draft
PSTO submits → status: submitted_to_ro → RO reviews
RO approves → status: submitted_to_ho → HO reviews
HO approves → status: submitted_to_ousec (OUSEC-RO receives)
OUSEC-RO approves → status: submitted_to_admin
ADMIN approves → status: submitted_to_superadmin
SUPER ADMIN approves → status: approved
```

### AGENCY Flow
```
AGENCY creates → status: draft
AGENCY submits → status: submitted_to_ho → AGENCY HO reviews
AGENCY HO approves → status: submitted_to_ousec 
                      → OUSEC-STS (if ssi/collegial/main cluster)
                      → OUSEC-RD (if council/rdi cluster)
OUSEC approves → status: submitted_to_admin
ADMIN approves → status: submitted_to_superadmin
SUPER ADMIN approves → status: approved
```

---

## 📊 Testing Checklist

After implementing all fixes, test:

### Creation Tests
- [ ] PSTO can create indicators
- [ ] RO can create indicators ✅ (fixed)
- [ ] AGENCY can create indicators
- [ ] SUPER ADMIN can create indicators ✅ (fixed)
- [ ] HO cannot create indicators (should fail)
- [ ] OUSEC cannot create indicators (should fail)
- [ ] ADMIN cannot create indicators (should fail, unless they're also SA)

### Visibility Tests
- [ ] PSTO sees only their own drafts ✅ (fixed)
- [ ] RO sees only their drafts + PSTO submissions for review ✅ (refined)
- [ ] AGENCY sees only their own agency drafts
- [ ] AGENCY HO sees only drafts from their specific agency ✅ (fixed statuses)
- [ ] HO sees only RO drafts from their region
- [ ] OUSEC-RO sees only PSTO/RO flow indicators
- [ ] OUSEC-STS sees only correct agency indicators (NAST, NRCP, PAGASA, etc.)
- [ ] OUSEC-RD sees only correct agency indicators (PCAARRD, PCHRD, ASTI, etc.)
- [ ] ADMIN sees ALL indicators
- [ ] SUPER ADMIN sees ALL indicators

### Edit Permission Tests
- [ ] PSTO can edit only their own drafts
- [ ] RO can edit only their own drafts (not PSTO's) ✅ (fixed)
- [ ] AGENCY can edit only their own drafts
- [ ] ADMIN can edit any indicator
- [ ] SUPER ADMIN can edit any indicator

### Workflow Routing Tests
- [ ] PSTO → RO → HO → OUSEC-RO → ADMIN → SUPER ADMIN → APPROVED
- [ ] AGENCY (NAST) → AGENCY HO → OUSEC-STS → ADMIN → SUPER ADMIN
- [ ] AGENCY (PCAARRD) → AGENCY HO → OUSEC-RD → ADMIN → SUPER ADMIN
- [ ] AGENCY (ASTI) → AGENCY HO → OUSEC-RD → ADMIN → SUPER ADMIN
- [ ] AGENCY (PAGASA) → AGENCY HO → OUSEC-STS → ADMIN → SUPER ADMIN

### Rejection Flow Tests
- [ ] RO rejects back to PSTO
- [ ] HO rejects back to RO
- [ ] AGENCY HO rejects back to AGENCY
- [ ] OUSEC rejects back to appropriate HO
- [ ] ADMIN rejects back to OUSEC
- [ ] SUPER ADMIN rejects back to ADMIN

---

## 🎯 Priority Order for Implementation

1. **CRITICAL (Implement First):**
   - Fix #1: Creation permissions (RO & SA cannot create)
   - Fix #2: Add missing STATUS_SUBMITTED_TO_SUPERADMIN constant
   - Fix #4: RO edit permissions (can edit PSTO drafts)

2. **HIGH (Implement Second):**
   - Fix #3: PSTO visibility (sees other PSTO drafts)
   - Fix #5: AGENCY HO visibility statuses
   - Fix #7: Enhance submitToOUSEC() for explicit routing

3. **MEDIUM (Implement Third):**
   - Fix #6: OUSEC routing in approve() method
   - Fix #8: Refine RO visibility scope

---

## 📝 Notes for Implementation

1. **Database Check:** Verify that `status` column in `objectives` table allows `submitted_to_superadmin` value
2. **NotificationService:** Update notification methods to accept OUSEC type parameter
3. **Testing:** Create test users for each role to verify permissions
4. **Logging:** Add detailed logging to approve() method to track routing decisions
5. **Documentation:** Update user manual with corrected workflow

---

**End of Analysis**
