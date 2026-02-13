# Repository Migration Guide — Private DOST Project

**Date:** February 13, 2026  
**Source:** Leisanre/private-project-m (mass-debugging branch)  
**Target:** Your new private repository

---

## ⚠️ CRITICAL: Migration Conflicts Detected

### Overlapping Migrations (Must Delete Before Running New 3NF Migration)

Your new 3NF migration (`2025_01_15_000001_normalize_database_to_3nf.php`) will **CONFLICT** with these existing migrations:

1. **`2026_01_13_033204_add_rejection_note_to_indicators_table.php`**
   - Adds `rejection_note` column to `objectives` table
   - **CONFLICT:** 3NF migration DROPS this column (it's duplicate of `rejection_reason`)
   - **ACTION:** DELETE this migration file from your new repo

2. **`2026_01_23_003120_add_email_notifications_enabled_to_users_table.php`**
   - Adds `email_notifications_enabled` to `users` table
   - **CONFLICT:** 3NF migration also adds this column
   - **ACTION:** DELETE this migration file from your new repo

3. **`2026_01_29_000001_create_rejection_notes_table.php`**
   - Creates separate `rejection_notes` table
   - **OK TO KEEP:** Works with 3NF migration (stores private reviewer notes)

### Migration Order Issues

Your 3NF migration is dated `2025_01_15` but the conflicting migrations are dated `2026_01_xx` (future dates). Since migrations run in chronological order, the **conflicting migrations will run FIRST** and cause failures.

**SOLUTIONS:**

**Option A (Recommended):** Delete conflicting migrations entirely since 3NF covers them
**Option B:** Rename 3NF migration to `2026_02_13_000001_normalize_database_to_3nf.php` (today's date)

---

## 📋 Step-by-Step Repository Migration

### 1. Initialize New Repository

```bash
cd /Users/keziamiravalles/Documents/nat/code/DOST

# Initialize git
git init
git branch -M main

# Create .gitignore
cat > .gitignore << 'EOF'
/vendor/
/node_modules/
/public/hot
/public/build
/public/storage
/storage/*.key
.env
.env.backup
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/.idea
/.vscode
.DS_Store
EOF

# Initial commit
git add .
git commit -m "Initial commit: Mass debugging version (from Leisanre/private-project-m)"
```

### 2. Clean Up Migration Conflicts

```bash
cd /Users/keziamiravalles/Documents/nat/code/DOST

# Delete conflicting migrations
rm database/migrations/2026_01_13_033204_add_rejection_note_to_indicators_table.php
rm database/migrations/2026_01_23_003120_add_email_notifications_enabled_to_users_table.php

# Commit cleanup
git add -A
git commit -m "chore: remove conflicting migrations (covered by 3NF migration)"
```

### 3. Create Your GitHub Repository

```bash
# Option 1: Using GitHub CLI (if installed)
gh repo create <your-username>/dost-mass-debugging --private

# Option 2: Manual
# Go to https://github.com/new
# - Repository name: dost-mass-debugging
# - Description: "Mass debugging and normalization of DOST M&E system"
# - Private: ✓
# - Do NOT initialize with README/gitignore (you already have them)

# Link your local repo to GitHub
git remote add origin https://github.com/<your-username>/dost-mass-debugging.git
git push -u origin main
```

---

## 📚 Knowledge Preservation Document

Since GitHub Copilot cannot retain conversation history across workspaces, here's what you need to preserve:

### Create Technical Documentation

```bash
cd /Users/keziamiravalles/Documents/nat/code/DOST
```

Then create the file below (I'll generate it next).

---

## 🔄 What Happens After Migration

### When You Open New Workspace in VS Code:

1. Close current workspace
2. Open `/Users/keziamiravalles/Documents/nat/code/DOST` folder
3. GitHub Copilot will start with **zero knowledge** of:
   - The bug report (BUG-REPORT-MANALOTO.md)
   - The 68+ bugs identified
   - The work we just completed
   - The database schema
   - The model architecture

### To Restore Context Quickly:

**Option 1:** Keep BUG-REPORT-MANALOTO.md and reference it
```bash
# If you haven't copied it yet:
cp /path/to/private-project-m/BUG-REPORT-MANALOTO.md \
   /Users/keziamiravalles/Documents/nat/code/DOST/
```

**Option 2:** Create a new session-init prompt (I'll generate this next)

---

## 📝 Files You Should Create in New Repo

I'll create these now:

1. `CODEBASE_CONTEXT.md` — Full system architecture + bug fixes
2. `SESSION_INIT_PROMPT.txt` — Copy-paste to start new Copilot sessions
3. `MIGRATION_CHECKLIST.md` — Steps to verify migration worked

Creating these now...
