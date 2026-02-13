# Indicator Lifecycle Documentation

## Overview

This document describes the complete indicator lifecycle including all status transitions, user actions, and approval/rejection flows for the Strategic Plan Management System.

---

## Status Constants Reference

| Status Constant | Display Name | Description |
|----------------|--------------|-------------|
| `STATUS_DRAFT` | DRAFT | Initial state when creating an indicator |
| `STATUS_SUBMITTED_TO_RO` | SUBMITTED TO RO | Submitted by PSTO, pending Regional Office review |
| `STATUS_RETURNED_TO_PSTO` | RETURNED TO PSTO | Rejected by RO, returned to PSTO for corrections |
| `STATUS_RETURNED_TO_AGENCY` | RETURNED TO AGENCY | Rejected by HO, returned to Agency for corrections |
| `STATUS_SUBMITTED_TO_HO` | SUBMITTED TO HO | Approved by RO, pending Head Office review |
| `STATUS_SUBMITTED_TO_ADMIN` | SUBMITTED TO ADMIN | Approved by HO, pending Administrator review |
| `STATUS_SUBMITTED_TO_SUPERADMIN` | SUBMITTED TO SUPERADMIN | Approved by Admin, pending SuperAdmin final approval |
| `STATUS_RETURNED_TO_RO` | RETURNED TO RO | Legacy status (rarely used) |
| `STATUS_RETURNED_TO_HO` | RETURNED TO HO | Rejected by Admin/SuperAdmin, returned to HO for review |
| `STATUS_RETURNED_TO_ADMIN` | RETURNED TO ADMIN | Legacy status (rarely used) |
| `STATUS_APPROVED` | APPROVED | Final approved state (only SuperAdmin can approve) |

---

## Workflow Diagrams

### Agency Workflow (Direct to HO)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           AGENCY WORKFLOW                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────┐      Submit      ┌─────────────────────────────────────┐     │
│  │  AGENCY   │ ───────────────► │   SUBMITTED TO HO                   │     │
│  │  (Maker)  │                  │   - Assigned HO reviews             │     │
│  │  DRAFT    │                  │   - If no HO assigned → Admin       │     │
│  └──────────┘                  └─────────────────────────────────────┘     │
│       ▲                              │                                     │
│       │                              │ Approve                             │
│       │                              ▼                                     │
│       │                    ┌─────────────────────┐                        │
│       │                    │ SUBMITTED TO ADMIN  │                        │
│       │                    └─────────────────────┘                        │
│       │                              │                                     │
│       │                              │ Approve                             │
│       │                              ▼                                     │
│       │                    ┌─────────────────────────────┐                │
│       │                    │ SUBMITTED TO SUPERADMIN      │                │
│       │                    └─────────────────────────────┘                │
│       │                              │                                     │
│       │                    ┌─────────┴─────────┐                           │
│       │                    │                   │                           │
│       │                    │ Approve           │ Reject                    │
│       │                    ▼                   ▼                           │
│       │              ┌──────────┐      ┌──────────────┐                   │
│       │              │ APPROVED │      │ RETURNED TO  │                   │
│       │              │          │      │ HO           │                   │
│       │              └──────────┘      └──────────────┘                   │
│       │                                                    │               │
│       │                                                    │ HO reviews    │
│       │                                                    │ and sends     │
│       │                                                    │ down          │
│       │                                                    ▼               │
│       │                                          ┌──────────────────┐      │
│       │                                          │ RETURNED TO       │      │
│       │                                          │ AGENCY            │      │
│       │                                          └──────────────────┘      │
│       │                                                    │               │
│       └────────────────────────────────────────────────────┘               │
│                    Agency edits and resubmits                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

### PSTO Workflow (Via RO)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PSTO WORKFLOW                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────┐      Submit      ┌─────────────────────────────────────┐     │
│  │   PSTO    │ ───────────────► │   SUBMITTED TO RO                  │     │
│  │  (Maker)  │                  │   - RO reviews submission           │     │
│  │  DRAFT    │                  └─────────────────────────────────────┘     │
│  └──────────┘                              │                             │
│       ▲                                    │                             │
│       │                            ┌───────┴───────┐                    │
│       │                            │               │                    │
│       │                      Approve│           Reject│                 │
│       │                            ▼               ▼                    │
│       │                  ┌──────────────┐  ┌──────────────────┐        │
│       │                  │ SUBMITTED TO  │  │ RETURNED TO       │        │
│       │                  │ HO            │  │ PSTO              │        │
│       │                  └──────────────┘  └──────────────────┘        │
│       │                            │                    │               │
│       │                            │ Approve            │               │
│       │                            ▼                    └───────────────┘│
│       │                  ┌─────────────────────┐          PSTO edits    │
│       │                  │ SUBMITTED TO ADMIN  │          and resubmits │
│       │                  └─────────────────────┘                        │
│       │                            │                                     │
│       │                            │ Approve                             │
│       │                            ▼                                     │
│       │                  ┌─────────────────────────────┐                │
│       │                  │ SUBMITTED TO SUPERADMIN      │                │
│       │                  └─────────────────────────────┘                │
│       │                            │                                     │
│       │                  ┌─────────┴─────────┐                           │
│       │                  │                   │                           │
│       │                  │ Approve           │ Reject                    │
│       │                  ▼                   ▼                           │
│       │            ┌──────────┐      ┌──────────────┐                   │
│       │            │ APPROVED │      │ RETURNED TO  │                   │
│       │            │          │      │ HO           │                   │
│       │            └──────────┘      └──────────────┘                   │
│       │                                                  │               │
│       │                                                  │ HO reviews    │
│       │                                                  │ and sends     │
│       │                                                  │ down          │
│       │                                                  ▼               │
│       │                                        ┌──────────────────┐      │
│       │                                        │ RETURNED TO       │      │
│       │                                        │ PSTO              │      │
│       │                                        └──────────────────┘      │
│       │                                                  │               │
│       └──────────────────────────────────────────────────┘               │
│                    PSTO edits and resubmits                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Rejection Flow Detail

### Rejection Path Summary

| Rejector Role | Target Status | Recipient | Notes |
|---------------|---------------|-----------|-------|
| **SuperAdmin** | `returned_to_ho` | HO | HO reviews then sends down to maker |
| **Administrator** | `returned_to_ho` | HO | HO reviews then sends down to maker |
| **Head of Office** | `returned_to_agency` or `returned_to_psto` | Original Maker | Direct rejection to creator |
| **Regional Office (RO)** | `returned_to_psto` | PSTO | HO is notified of rejection |

### Rejection Flow Logic (from `Objective.php:364-428`)

```php
public function reject(User $rejector, ?string $reason = null)
{
    // SuperAdmin and Administrator reject to HO
    if ($rejector->isSA() || $rejector->isAdministrator()) {
        $newStatus = self::STATUS_RETURNED_TO_HO;
        $recipientId = $this->determineHORecipientForReturn();
    }
    // HO rejects to original maker
    elseif ($rejector->canActAsHeadOfOffice()) {
        if ($this->submitter && $this->submitter->isAgency()) {
            $newStatus = self::STATUS_RETURNED_TO_AGENCY;
            $recipientId = $this->submitter_id;
        } else {
            // For PSTO workflow, send to PSTO directly
            $newStatus = self::STATUS_RETURNED_TO_PSTO;
            $recipientId = $this->determineOriginalMakerId();
        }
    }
    // RO rejects to PSTO (HO gets notified)
    elseif ($rejector->isRO()) {
        $newStatus = self::STATUS_RETURNED_TO_PSTO;
        $recipientId = $this->submitted_by_user_id;
        $this->notifyHOAboutRejection($rejector, $reason);
    }
}
```

### The "Send Down" Process (HO Intermediate Step)

When SA or Admin rejects an indicator, it goes to HO first. HO then has two options:

1. **Review and Send Down to Maker** - HO clicks "Send Down to Maker" button
2. **Direct Reject** - HO can also directly reject (bypassing the two-step process)

The `sendDownToMaker()` method handles the forwarding from HO to the original maker.

---

## User Actions by Role

### Agency User

| Action | Description | Valid From Statuses | Resulting Status |
|--------|-------------|---------------------|------------------|
| Create | Create new indicator | N/A | `DRAFT` |
| Submit | Submit to HO | `DRAFT`, `returned_to_agency` | `submitted_to_ho` |
| Edit | Edit returned indicator | `returned_to_agency` | No change (still `returned_to_agency`) |
| Resubmit | Forward after editing | `returned_to_agency` | `submitted_to_ho` |

### PSTO User

| Action | Description | Valid From Statuses | Resulting Status |
|--------|-------------|---------------------|------------------|
| Create | Create new indicator | N/A | `DRAFT` |
| Submit | Submit to RO | `DRAFT`, `returned_to_psto` | `submitted_to_ro` |
| Edit | Edit returned indicator | `returned_to_psto` | No change (still `returned_to_psto`) |
| Resubmit | Forward after editing | `returned_to_psto` | `submitted_to_ro` |

### Regional Office (RO)

| Action | Description | Valid From Statuses | Resulting Status |
|--------|-------------|---------------------|------------------|
| Review | View submitted indicator | `submitted_to_ro` | No change |
| Approve | Forward to HO | `submitted_to_ro` | `submitted_to_ho` |
| Reject | Return to PSTO with notes | `submitted_to_ro` | `returned_to_psto` |

### Head of Office (HO)

| Action | Description | Valid From Statuses | Resulting Status |
|--------|-------------|---------------------|------------------|
| Review | View submitted indicator | `submitted_to_ho` | No change |
| Approve | Forward to Admin | `submitted_to_ho` | `submitted_to_admin` |
| Reject | Direct reject to maker | `submitted_to_ho` | `returned_to_agency` or `returned_to_psto` |
| Send Down | Forward rejection from above | `returned_to_ho` | `returned_to_agency` or `returned_to_psto` |

### Administrator

| Action | Description | Valid From Statuses | Resulting Status |
|--------|-------------|---------------------|------------------|
| Review | View submitted indicator | `submitted_to_admin` | No change |
| Approve | Forward to SuperAdmin | `submitted_to_admin` | `submitted_to_superadmin` |
| Reject | Return to HO for review | `submitted_to_admin` | `returned_to_ho` |

### SuperAdmin

| Action | Description | Valid From Statuses | Resulting Status |
|--------|-------------|---------------------|------------------|
| Review | View submitted indicator | `submitted_to_superadmin` | No change |
| Approve | Final approval | `submitted_to_superadmin` | `approved` |
| Reject | Return to HO for review | `submitted_to_superadmin` | `returned_to_ho` |

---

## Rejection Notes System

The `RejectionNote` model stores private rejection notes that are only visible to specific recipients.

### Rejection Note Fields

| Field | Description |
|-------|-------------|
| `objective_id` | The indicator being rejected |
| `rejected_by_user_id` | The user who rejected |
| `visible_to_user_id` | The user who can see this note |
| `note` | The rejection reason/notes |

### Note Visibility

- When SA/Admin rejects to HO: Note is visible to HO
- When HO sends down to maker: New note created for maker
- Original rejection note is shown to HO when they receive the rejection

---

## UI Buttons and Actions

### Based on Status (from `objective-view.blade.php`)

**Returned Indicators** (`str_starts_with(status, 'returned_to_')`):
- **Edit** - Edit the indicator
- **Send To (Resubmit)** - Forward to next level
- **HO Special**: "Send Down to Maker" button instead

**Pending Indicators** (not APPROVED):
- **Reject** - Start rejection process
- **Approve** - Approve and forward to next level

**Approved Indicators**:
- No action buttons (locked)

---

## Database Timestamps

The system tracks when an indicator reaches each level:

| Field | Description |
|-------|-------------|
| `submitted_to_ro_at` | Timestamp when submitted to RO |
| `submitted_to_ho_at` | Timestamp when submitted to HO |
| `submitted_to_admin_at` | Timestamp when submitted to Admin |
| `submitted_to_superadmin_at` | Timestamp when submitted to SuperAdmin |
| `approved_at` | Timestamp when finally approved |
| `rejected_at` | Timestamp when rejected |

---

## Code Reference

### Model Methods (`app/Models/Objective.php`)

| Method | Line | Purpose |
|--------|------|---------|
| `submitToRO()` | 207-223 | PSTO submits to RO |
| `submitToHO()` | 225-257 | Agency submits to HO (or RO forwards) |
| `submitToRegionalHead()` | 263-279 | PSTO Office Head forwards to RO |
| `submitToAdmin()` | 281-297 | HO forwards to Admin |
| `submitToSuperAdmin()` | 299-315 | Admin forwards to SuperAdmin |
| `approve()` | 317-362 | Handle approval (or forward) logic |
| `reject()` | 364-428 | Handle rejection routing |
| `sendDownToMaker()` | 497-549 | HO sends rejection down to maker |
| `forward()` | 555-602 | Makers resubmit after editing |

### Livewire Component (`app/Livewire/Admin/ObjectiveView.php`)

| Method | Purpose |
|--------|---------|
| `approve()` | Final approval action |
| `startReject()` | Show rejection form |
| `reject()` | Process rejection |
| `forward()` | Forward returned indicator |
| `startSendDown()` | HO prepares to send down |
| `sendDownToMaker()` | HO sends down to maker |

---

## Notification Types

### NotificationService Methods

| Method | Purpose |
|--------|---------|
| `notifyAgencySubmissionToHO()` | Notify HO of agency submission |
| `notifyIndicatorSubmitted()` | Notify RO/Admin of new submission |
| `notifyIndicatorApproved()` | Notify submitter of approval |
| `notifyIndicatorRejected()` | Notify recipient of rejection |
| `notifyHOAboutRejection()` | Notify HO of RO rejection |
| `notifyIndicatorForwarded()` | Notify of forwarding |

---

## Role Detection Methods

### User Model Methods

| Method | Description |
|--------|-------------|
| `isSA()` | User is SuperAdmin |
| `isAdministrator()` | User is Admin or SuperAdmin |
| `isRO()` / `isRegionalOffice()` | User is Regional Office |
| `canActAsHeadOfOffice()` | User can act as HO (role OR assignment) |
| `isAgency()` | User is Agency |
| `isPsto()` | User is PSTO |
| `isHeadOfficer()` | User has head_officer role |

---

## Key Implementation Details

1. **Locking**: Indicators are locked (`is_locked = true`) when submitted/approved, unlocked when returned
2. **History**: All state transitions are recorded in `indicator_histories` table
3. **Audit**: Administrative actions logged to `audit_logs` table
4. **Private Notes**: Rejection notes are private - only visible to specified recipient
5. **HO Assignment**: HOs can be assigned via agency, region, or office `head_user_id`
6. **Direct Agency Flow**: Agencies with no HO assigned skip directly to Admin

---

*Documentation generated from codebase analysis - January 2026*
