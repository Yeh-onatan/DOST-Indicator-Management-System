#!/bin/bash
# Repository Setup Script
# Run this after copying files to /Users/keziamiravalles/Documents/nat/code/DOST

set -e  # Exit on error

echo "🚀 Starting repository setup..."

cd /Users/keziamiravalles/Documents/nat/code/DOST

# Step 1: Delete conflicting migrations
echo "📝 Step 1: Removing conflicting migrations..."
rm -f database/migrations/2026_01_13_033204_add_rejection_note_to_indicators_table.php
rm -f database/migrations/2026_01_23_003120_add_email_notifications_enabled_to_users_table.php
echo "   ✅ Conflicting migrations removed"

# Step 2: Initialize git repository
echo "📝 Step 2: Initializing git repository..."
git init
git branch -M main
echo "   ✅ Git initialized"

# Step 3: Create .gitignore if it doesn't exist
if [ ! -f .gitignore ]; then
    echo "📝 Step 3: Creating .gitignore..."
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
    echo "   ✅ .gitignore created"
else
    echo "   ℹ️  .gitignore already exists, skipping"
fi

# Step 4: Copy bug report if it exists
if [ -f /Users/keziamiravalles/Documents/nat/code/DOST/private-project-m/BUG-REPORT-MANALOTO.md ]; then
    echo "📝 Step 4: Copying bug report..."
    cp /Users/keziamiravalles/Documents/nat/code/DOST/private-project-m/BUG-REPORT-MANALOTO.md .
    echo "   ✅ Bug report copied"
else
    echo "   ⚠️  Bug report not found at expected location, skipping"
fi

# Step 5: Initial commit
echo "📝 Step 5: Creating initial commit..."
git add .
git commit -m "Initial commit: Mass debugging version

Source: Leisanre/private-project-m (mass-debugging branch)
Date: February 13, 2026

Phase 1-2 Complete:
- Database normalization to 3NF
- Model merge (Indicator + Objective)
- 13 critical bugs fixed
- Lazy loading fix (N+1 elimination)
- Try-catch wrapping
- Status normalization

See CODEBASE_CONTEXT.md for full details."
echo "   ✅ Initial commit created"

# Step 6: Display next steps
echo ""
echo "✅ Repository setup complete!"
echo ""
echo "📋 Next Steps:"
echo ""
echo "1. Create GitHub repository:"
echo "   Go to: https://github.com/new"
echo "   Name: dost-mass-debugging (or your choice)"
echo "   Visibility: Private ✓"
echo "   Do NOT initialize with README"
echo ""
echo "2. Link and push to GitHub:"
echo "   git remote add origin https://github.com/<your-username>/dost-mass-debugging.git"
echo "   git push -u origin main"
echo ""
echo "3. Verify migration safety:"
echo "   Read MIGRATION_CHECKLIST.md"
echo ""
echo "4. Open in VS Code:"
echo "   code /Users/keziamiravalles/Documents/nat/code/DOST"
echo ""
echo "5. Start new Copilot session:"
echo "   Copy-paste content from SESSION_INIT_PROMPT.txt"
echo ""
echo "📚 Documentation created:"
echo "   - REPOSITORY_MIGRATION_GUIDE.md"
echo "   - CODEBASE_CONTEXT.md"
echo "   - SESSION_INIT_PROMPT.txt"
echo "   - MIGRATION_CHECKLIST.md"
echo "   - This setup script"
echo ""
