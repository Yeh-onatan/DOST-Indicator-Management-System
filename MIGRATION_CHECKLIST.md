# Migration Verification Checklist

**After running the 3NF migration, verify these changes:**

---

## ✅ Pre-Migration Cleanup

- [ ] **Delete conflicting migration:** `2026_01_13_033204_add_rejection_note_to_indicators_table.php`
- [ ] **Delete conflicting migration:** `2026_01_23_003120_add_email_notifications_enabled_to_users_table.php`
- [ ] **Keep:** `2026_01_29_000001_create_rejection_notes_table.php` (works with 3NF)

---

## 🗄️ Database Schema Verification

### Run Migration
```bash
php artisan migrate
```

### Verify Tables Modified

#### 1. `objectives` Table
```sql
DESCRIBE objectives;
```

**Expected changes:**
- ✅ `rejection_note` column DROPPED (was duplicate)
- ✅ `deleted_at` column ADDED (SoftDeletes)
- ✅ All `'DRAFT'` values changed to `'draft'`
- ✅ Indexes added on `created_by`, `updated_by`, `owner_id`

**Verify data migration:**
```sql
-- Check if rejection_reason has the migrated notes
SELECT id, rejection_reason FROM objectives WHERE rejection_reason IS NOT NULL LIMIT 5;

-- Check status normalization
SELECT DISTINCT status FROM objectives;
-- Should see: 'draft', 'submitted_to_ro', etc. (all lowercase)
```

#### 2. `users` Table
```sql
DESCRIBE users;
```

**Expected changes:**
- ✅ `email_notifications_enabled` ADDED (boolean, default true)
- ✅ `last_login_at` ADDED (timestamp, nullable)
- ✅ `is_locked` ADDED (boolean, default false)
- ✅ `deleted_at` ADDED (SoftDeletes)

**Verify defaults:**
```sql
SELECT COUNT(*) FROM users WHERE email_notifications_enabled = 1;
-- Should match total user count
```

#### 3. `agencies` Table (now `dost_agencies`)
```sql
DESCRIBE dost_agencies;
```

**Expected changes:**
- ✅ `agency_id` column DROPPED (was redundant varchar)
- ✅ `deleted_at` ADDED (SoftDeletes)

**Verify no data loss:**
```sql
SELECT COUNT(*) FROM dost_agencies;
-- Should match previous count
```

#### 4. `pillars`, `outcomes`, `strategies` Tables
```sql
DESCRIBE pillars;
DESCRIBE outcomes;
DESCRIBE strategies;
```

**Expected changes:**
- ✅ `name` column ADDED to all three tables

**Verify data:**
```sql
-- Check if name was populated from existing data
SELECT id, code, name FROM pillars LIMIT 5;
SELECT id, code, name FROM outcomes LIMIT 5;
SELECT id, code, name FROM strategies LIMIT 5;
```

---

## 🧪 Code Verification

### Run Syntax Checks
```bash
php -l app/Models/Objective.php
php -l app/Models/Indicator.php
php -l app/Models/User.php
php -l app/Models/DOSTAgency.php
php -l app/Livewire/Dashboard/UnifiedDashboard.php
```

### Run Basic Tests (if you have any)
```bash
php artisan test
# OR
./vendor/bin/pest
```

### Test Key Workflows

#### 1. Test Lazy Loading Fix
```php
// In tinker: php artisan tinker
use App\Models\Objective;

// This should trigger only 1 query + eager loads (not N+1)
$objectives = Objective::limit(10)->get();
foreach ($objectives as $obj) {
    echo $obj->regionRelation?->name . "\n";  // No extra query
    echo $obj->office?->name . "\n";           // No extra query
    echo $obj->submitter?->name . "\n";        // No extra query
}
```

#### 2. Test Status Constants
```php
// In tinker
use App\Models\Objective;

$draft = Objective::where('status', Objective::STATUS_DRAFT)->count();
echo "Draft count: $draft\n";

// Should work (all normalized to lowercase)
$allStatuses = Objective::select('status')->distinct()->get();
foreach ($allStatuses as $s) {
    echo $s->status . "\n";  // Should be all lowercase
}
```

#### 3. Test Model Alias
```php
// In tinker
use App\Models\Indicator;
use App\Models\Objective;

// These should be the same class
echo get_class(new Indicator()) . "\n";  // App\Models\Objective
echo get_class(new Objective()) . "\n";  // App\Models\Objective

// Indicator methods should work
$indicator = Indicator::first();
echo $indicator->status . "\n";  // Should work
```

#### 4. Test Password Hash Fix
```php
// In tinker
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::first();

// This should work now (was broken before)
$result = $user->hasUsedPasswordBefore('test-password-123');
echo "Password check result: " . ($result ? 'true' : 'false') . "\n";
```

---

## 🔍 Rollback Plan (If Something Goes Wrong)

### Rollback Migration
```bash
php artisan migrate:rollback --step=1
```

### Restore from Backup
```sql
-- If you made a backup before migrating
mysql -u root -p dbDIMS < dbDIMS_backup_2026-02-13.sql
```

### Check Migration Status
```bash
php artisan migrate:status
```

---

## ✅ Sign-Off Checklist

- [ ] All migrations ran successfully (no errors)
- [ ] `objectives` table schema verified
- [ ] `users` table schema verified
- [ ] `agencies` table schema verified
- [ ] `pillars/outcomes/strategies` tables have `name` column
- [ ] SoftDeletes working (deleted_at columns exist)
- [ ] Status values all lowercase ('draft' not 'DRAFT')
- [ ] Lazy loading working (no N+1 queries)
- [ ] Indicator alias working (extends Objective)
- [ ] Password hash comparison working
- [ ] No PHP syntax errors
- [ ] Application loads without errors
- [ ] Dashboard displays correctly
- [ ] Forms submit successfully

---

## 🆘 Troubleshooting

### "Column 'rejection_note' already exists"
**Cause:** Conflicting migration not deleted  
**Fix:** Delete `2026_01_13_033204_add_rejection_note_to_indicators_table.php` and re-run

### "Column 'email_notifications_enabled' already exists"
**Cause:** Conflicting migration not deleted  
**Fix:** Delete `2026_01_23_003120_add_email_notifications_enabled_to_users_table.php` and re-run

### "Class 'Agency' not found"
**Cause:** Old code still references non-existent model  
**Fix:** Search codebase for `use App\Models\Agency;` and replace with `DOSTAgency`

### "Call to undefined method addHistory()"
**Cause:** Old code calling deprecated method  
**Fix:** Already fixed in Objective model (alias added). If still seeing this, clear cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

**End of Checklist**
