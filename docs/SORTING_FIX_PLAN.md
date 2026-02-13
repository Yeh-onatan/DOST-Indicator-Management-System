# Dashboard Sorting Fix Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix the sorting functionality for Pillar/Outcome/Strategy/Target columns without the current complexity and click-through issues.

**Architecture:** Move sorting controls to the filters sidebar as dropdown selects, avoiding dynamic column header buttons that have Livewire v3 compatibility issues.

**Tech Stack:** Livewire v3, Alpine.js, Tailwind CSS

---

## Problem Summary

After 3 hours of troubleshooting:
- Pillar/Outcome/Strategy are dynamic fields (only show when "Strategic Plan" category is selected)
- Click events on sort headers fall through to inline edit buttons
- `$wire.sortBy is not a function` errors with various approaches
- Complex z-index and event propagation issues

## Solution: Sidebar Sorting Controls

Add "Sort By" dropdown to the filters sidebar that:
1. Works independently of category selection
2. Uses standard Livewire `wire:model.live` for state updates
3. Shows available sort options dynamically
4. Has clear visual feedback for current sort
5. Avoids all click-through and z-index issues

---

### Task 1: Add Sort Controls to Sidebar

**Files:**
- Modify: `resources/views/livewire/dashboard/unified-dashboard.blade.php`

**Step 1: Add sort dropdown to filters sidebar (after existing filters)**

Find the filters section (around line 350-370) and add after the "Clear" button:

```blade
@php
    $sortOptions = [
        ['value' => 'created_at', 'label' => 'Date Created'],
        ['value' => 'target_value', 'label' => 'Target Value'],
    ];
    // Only add pillar/outcome/strategy if Strategic Plan category is selected
    if ($categoryFilter && strtolower($categoryFilter) === 'strategic plan') {
        $sortOptions[] = ['value' => 'pillar_value', 'label' => 'Pillar'];
        $sortOptions[] = ['value' => 'outcome_value', 'label' => 'Outcome'];
        $sortOptions[] = ['value' => 'strategy_value', 'label' => 'Strategy'];
    }
@endphp

<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-gray-700">Sort By</label>
    <select wire:model.live="sortBy" class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        @foreach($sortOptions as $option)
            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
        @endforeach
    </select>
</div>

<div class="flex items-center gap-2">
    <button wire:click="toggleSortDirection" class="flex-1 h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 hover:bg-gray-50 transition flex items-center justify-center gap-1">
        @if($sortDirection === 'asc')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m-4 4l4-4" />
            </svg>
            <span>Ascending</span>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m4-4l4 4m-4-4l4-4" />
            </svg>
            <span>Descending</span>
        @endif
    </button>
</div>
```

**Step 2: Remove problematic sort buttons from column headers**

Replace all the pillar/outcome/strategy/target `<button>` elements in the `<thead>` with simple `<th>` elements:

```blade
@if($catField['field_name'] === 'pillar')
    <th class="px-4 py-3 font-semibold @if($sortBy === 'pillar_value') bg-blue-100 @endif">
        Pillar
        @if($sortBy === 'pillar_value')
            <span class="ml-1 text-lg @if($sortDirection === 'asc') text-blue-600 @else text-blue-800 @endif">
                @if($sortDirection === 'asc') ▲ @else ▼ @endif
            </span>
        @endif
    </th>
@endif
```

(Repeat same pattern for outcome, strategy, and target columns)

**Step 3: Add toggleSortDirection method to PHP component**

Add to `app/Livewire/Dashboard/UnifiedDashboard.php`:

```php
public function toggleSortDirection(): void
{
    $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
}
```

**Step 4: Test the implementation**

1. Load dashboard
2. Select "Strategic Plan" category
3. Verify Pillar/Outcome/Strategy appear in "Sort By" dropdown
4. Select different sort options
5. Toggle sort direction
6. Verify table updates correctly
7. Verify NO inline edit opens when sorting

---

### Task 2: Clean Up Existing Code

**Files:**
- Modify: `app/Livewire/Dashboard/UnifiedDashboard.php`
- Modify: `resources/views/livewire/dashboard/unified-dashboard.blade.php`

**Step 1: Verify sortBy method exists and works**

The method should already exist (line ~235):
```php
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
```

**Step 2: Remove any leftover button code**

Remove all `@click.stop="$wire.call(...)"` and `wire:click="sortBy(...)"` from `<thead>` section.

Search for: `@click.*sortBy` and `wire:click.*sortBy` - remove these from header cells.

**Step 3: Verify sorting query is correct**

The LEFT JOIN sorting should be in place (line ~2405):
```php
$query->when($this->sortBy === 'pillar_value', function ($q) {
    return $q->leftJoin('pillars as pillar_sort', 'objectives.pillar_id', '=', 'pillar_sort.id')
        ->orderBy('pillar_sort.value', $this->sortDirection);
})
```

---

## Testing Checklist

- [ ] Sort dropdown appears in sidebar
- [ ] Pillar/Outcome/Strategy options only show when "Strategic Plan" is selected
- [ ] Selecting sort option sorts the table
- [ ] Sort direction button toggles between ascending/descending
- [ ] Visual indicator (▲/▼) shows on active sort column
- [ ] No inline edit opens when sorting
- [ ] Sorting works with other filters applied
- [ ] Pagination works correctly after sorting
- [ ] Target value sorting works for all categories

---

## Rollback Plan

If issues arise:
1. Revert `unified-dashboard.blade.php` to previous version
2. Revert `UnifiedDashboard.php` to previous version
3. Keep LEFT JOIN sorting query (it works correctly)
4. Consider alternative: add sort as a separate top bar above filters
