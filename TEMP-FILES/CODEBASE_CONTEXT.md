# DOST M&E System — Codebase Context & Architecture

**Last Updated:** February 13, 2026  
**Version:** Phase 1-2 Mass Debugging Complete  
**Repository:** Private fork of Leisanre/private-project-m

---

## 🏗️ System Architecture

### Tech Stack
- **Backend:** Laravel 11 + PHP 8.2.4
- **Frontend:** Livewire 3 + Tailwind CSS
- **Database:** MariaDB 10.4.28 (MySQL compatible)
- **Database Name:** `dbDIMS`

### Key Directories
```
app/
├── Models/               # 24 Eloquent models (see below)
├── Livewire/            # 25 Livewire components
│   ├── Dashboard/       # UnifiedDashboard.php (3600+ lines)
│   ├── Admin/           # Approvals, OUSEC, etc.
│   ├── Proponent/       # ObjectiveForm.php
│   └── Indicators/      # UnifiedIndicatorForm.php
├── Services/            # NotificationService, AuditService
└── Constants/           # AgencyConstants.php

database/
├── migrations/          # 90+ migration files
└── seeders/

resources/views/         # Blade templates
routes/web.php          # All HTTP routes
```

---

## 📊 Database Schema (36 Tables)

### Core Tables

**`objectives`** (was called `indicators` — now unified)
- Main entity for M&E indicators
- **963 lines of logic in Objective.php model**
- Tracks status workflow: draft → submitted → approved
- Related: chapters, offices, regions, agencies, users

**`users`**
- Roles: super_admin, administrator, psto, regional_head, agency, ousec
- Regional/office assignments
- SoftDeletes enabled

**`offices`**
- Hierarchical structure (parent_office_id)
- Types: HO (Head Office), RO (Regional), PSTO

**`agencies`** (now `dost_agencies`)
- DOST agencies organized into clusters
- Cluster types: SSI, Collegial, Council, RDI

**`philippine_regions`**
- 17 regions + NCR
- Links to offices and objectives

### Lookup Tables
- `pillars` — Strategic plan pillars
- `outcomes` — Strategic plan outcomes  
- `strategies` — Strategic plan strategies
- `chapters` — Indicator categories/chapters
- `indicator_categories` — Category definitions

### Workflow Tables
- `rejection_notes` — Private reviewer feedback
- `indicator_histories` — Audit trail of status changes
- `proofs` — File uploads with MFO references
- `audit_logs` — System-wide audit trail

---

## 🔧 Recent Major Changes (Phase 1-2)

### 1. Database Normalization to 3NF

**Migration:** `2025_01_15_000001_normalize_database_to_3nf.php`

Changes:
- Dropped `agencies.agency_id` varchar (redundant)
- Added `name` column to `pillars`, `outcomes`, `strategies`
- Dropped `objectives.rejection_note` (duplicate of `rejection_reason`)
- Added indexes on `objectives.created_by/updated_by/owner_id`
- Added SoftDeletes (`deleted_at`) to `objectives`, `users`, `agencies`
- Added `email_notifications_enabled`, `last_login_at`, `is_locked` to `users`
- Normalized all 'DRAFT' → 'draft' in objectives table
- Added composite indexes for common queries

### 2. Model Architecture Consolidation

**Before:** 
- `Indicator.php` (943 lines) + `Objective.php` (963 lines) = 99% duplicate code
- Every bug had to be fixed twice

**After:**
- `Objective.php` — Canonical model (~750 lines)
- `Indicator.php` — Thin alias: `class Indicator extends Objective {}`
- All imports work unchanged: `use App\Models\Indicator;`

**13 Models Updated:**
- `Pillar`, `Outcome`, `Strategy`, `Chapter`, `Office`, `PhilippineRegion`
- `RejectionNote`, `Proof`, `IndicatorHistory`, `IndicatorMandatoryAssignment`
- All `hasMany(Indicator::class)` → `hasMany(Objective::class)`
- All `belongsTo(Indicator::class)` → `belongsTo(Objective::class)`

### 3. Lazy Loading Fix (N+1 Elimination)

**Problem:** Dashboard table with 100 rows = 300+ database queries

**Fix in `Objective.php`:**
```php
protected $with = ['regionRelation', 'office', 'submitter'];
```

**Why `regionRelation` not `region`?**
- `getRegionAttribute()` accessor was colliding with relationship name
- Renamed relationship, added `region()` as safe alias

### 4. Status Constants Normalization

**Before:** Mixed `'DRAFT'`, `'Draft'`, `'draft'` causing comparison bugs

**After:** All use constants:
```php
Objective::STATUS_DRAFT              // 'draft'
Objective::STATUS_SUBMITTED_TO_RO    // 'submitted_to_ro'
Objective::STATUS_SUBMITTED_TO_HO    // 'submitted_to_ho'
Objective::STATUS_SUBMITTED_TO_OUSEC // 'submitted_to_ousec'
Objective::STATUS_SUBMITTED_TO_ADMIN // 'submitted_to_admin'
Objective::STATUS_APPROVED           // 'approved'
Objective::STATUS_REJECTED           // 'rejected'
```

---

## 🐛 Bug Fixes Applied

### Critical Bugs Fixed

| ID | File | Bug | Fix |
|----|------|-----|-----|
| 1.1 | `User.php` | `canReviewAgency(?Agency)` — class doesn't exist | Changed to `?DOSTAgency` |
| 1.2 | `UnifiedDashboard.php` | Called `addHistory()` — method doesn't exist | Changed to `recordHistory()` + added alias |
| 1.10 | `User.php`, `PasswordHistory.php` | `hash_equals()` on bcrypt — always false | Changed to `Hash::check()` |
| 1.12 | `routes/web.php` | GET for `mark-read` — security issue | Changed to POST |
| 2.1 | `ObjectiveForm.php` | `submitAndForwardToHO()` data loss | Fixed: query latest draft instead of cleared ID |
| 2.4 | `Objective.php` | `$this->submitter_id` — column doesn't exist | Changed to `$this->submitted_by_user_id` |

### Other Fixes
- Fixed `isAdministrator()` incorrectly including super_admin
- Fixed typo `'sucbmit_to_ouse'` → `'submit_to_ousec'`
- Removed orphaned fields from `AdminSetting.php`
- Added null safety in `scopeAuthorized()`
- Replaced hardcoded `'DOST-CO'` with `AgencyConstants::DOST_CO`

---

## 🛡️ Error Handling (Try-Catch Wrapping)

### Wrapped Methods (20+)

**Objective.php (Model):**
- All workflow methods: `submit()`, `approve()`, `reject()`, `delete()`
- All status transition methods

**UnifiedDashboard.php (Livewire):**
- `saveAccomplishments`, `saveYearActual`, `saveYearWithProof`
- `submitToRO`, `submitToHO`, `submitToAdmin`, `submitToSuperAdmin`
- `approve`, `reject`, `delete`
- `ousecApprove`, `ousecReject`
- `executeAdminAction`, `executeBulkAction`, `executeForceStatus`

**Other Livewire Components:**
- `Approvals.php`: `approve()`, `reject()`
- `OUSECDashboard.php`: `approve()`, `reject()`
- `ObjectiveForm.php`: `submitObjective()`, `submitAndForwardToHO()`

**Pattern:**
```php
try {
    // Business logic
} catch (\Illuminate\Validation\ValidationException $e) {
    throw $e; // Let Livewire handle validation display
} catch (\Throwable $e) {
    \Log::error('Method failed', ['context' => $data, 'error' => $e->getMessage()]);
    $this->dispatch('toast', message: 'User-friendly error', type: 'error');
}
```

---

## 🔄 Workflow States & Routing

### Approval Flow by Role

```
PSTO → RO → HO → OUSEC → Admin → SuperAdmin → APPROVED
 ↓      ↓    ↓     ↓       ↓        ↓
      REJECTED (can be reopened by admin)
```

**Special Cases:**
- **Agency users:** Skip RO, go directly to HO
- **OUSEC clusters:** Routed based on `AgencyConstants::isOUSECSTSCluster()`

### Key Model Methods

**`Objective::submitToRO()`** — PSTO submits to Regional Office  
**`Objective::submitToHO()`** — RO/Agency submits to Head Office  
**`Objective::submitToOUSEC()`** — HO forwards to OUSEC (cluster-based)  
**`Objective::submitToAdmin()`** — OUSEC forwards to Admin  
**`Objective::approve($user)`** — Role-based auto-routing  
**`Objective::reject($user, $reason)`** — Creates `RejectionNote`, sends back  

---

## 🎯 Common Gotchas & Pitfalls

### 1. Status Comparison Case Sensitivity
❌ `if ($objective->status === 'DRAFT')`  
✅ `if ($objective->status === Objective::STATUS_DRAFT)`

### 2. Using Wrong Model Class
❌ `use App\Models\Agency;` (doesn't exist)  
✅ `use App\Models\DOSTAgency;`

### 3. Password Hashing Comparison
❌ `hash_equals($hash1, $hash2)` — bcrypt produces different hashes  
✅ `Hash::check($plaintext, $hashedPassword)`

### 4. Missing Relationship Eager Loading
❌ `Objective::all()` — triggers N+1 queries  
✅ Already fixed with `protected $with = [...]` in model

### 5. Method Name Confusion
❌ `$objective->addHistory(...)`  
✅ `$objective->recordHistory(...)`  
ℹ️ `addHistory()` now exists as alias for backward compat

---

## 📦 Key Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "livewire/livewire": "^3.0",
    "laravel/fortify": "^1.0"
  }
}
```

---

## 🔍 Where to Find Things

### User Authentication & Roles
- Model: `app/Models/User.php`
- Auth logic: `app/Providers/FortifyServiceProvider.php`
- Routes: `routes/web.php` — uses `auth` middleware

### Dashboard (Main UI)
- Component: `app/Livewire/Dashboard/UnifiedDashboard.php` (3600 lines)
- View: `resources/views/livewire/dashboard/unified-dashboard.blade.php`
- Filter/search logic in component
- Table rendering with inline editing

### Approval Workflow
- Admin: `app/Livewire/Admin/Approvals.php`
- OUSEC: `app/Livewire/Admin/OUSECDashboard.php`
- Model logic: `app/Models/Objective.php` — methods `approve()`, `reject()`, `submitTo*()`

### Forms (Create/Edit Indicators)
- Quick Form: In `UnifiedDashboard.php` — `saveQuickForm()` method
- Full Form: `app/Livewire/Proponent/ObjectiveForm.php`

---

## 🚨 Known Issues & TODOs

### Remaining from Bug Report (68+ bugs total)

**Phase 1-2 Completed** ✅  
**Phase 3-8 Pending** ⬜

**High Priority:**
- Debug logging left in production (`\Log::info` in `scopeAuthorized`)
- More Livewire methods need try-catch (minor data entry forms)
- Some hardcoded status strings still exist in blade templates

**Medium Priority:**
- Reporting windows (`ReportingWindow` model) — complex locking logic
- Data quality rules validation could be more robust
- Test coverage near zero

**Low Priority:**
- Code cleanup (dead comments, unused variables)
- Performance optimization beyond N+1 fix
- UI/UX inconsistencies

---

## 📖 How to Use This Document

### For New Copilot Sessions

Copy-paste this prompt at start of each session:

```
I'm working on a Laravel 11 + Livewire 3 M&E system for DOST Philippines.

Key facts:
- Database: MariaDB, 36 tables, dbDIMS database
- Main model: Objective (was Indicator, now merged)
- Workflow: PSTO → RO → HO → OUSEC → Admin → Approved
- Recent work: Phase 1-2 mass debugging (DB normalization, model merge, bug fixes)

Please read /CODEBASE_CONTEXT.md for full architecture.
I have a bug report with 68+ issues in BUG-REPORT-MANALOTO.md.

Current branch: mass-debugging
```

Then reference specific sections of this doc as needed.

---

**End of Context Document**
