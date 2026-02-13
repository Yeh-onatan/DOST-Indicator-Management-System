<x-layouts.app :title="'Indicators Report'">
  <div class="p-4 md:p-8 space-y-6">

    {{-- Header + Actions --}}
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-[var(--text)]">Indicators Report</h1>
        <p class="text-[var(--text-muted)] mt-1">
          Search and export Monitoring & Evaluation indicators recorded in the system.
        </p>
      </div>

      <div class="flex items-center gap-2">
        @php $qs = http_build_query(request()->query()); @endphp

        <a href="{{ route('reports.indicators.csv') }}@if($qs)?{{ $qs }}@endif"
           class="px-3 py-2 rounded-md border border-[var(--border)] text-[var(--text)] hover:bg-[var(--bg)]">
          Export CSV
        </a>

        <a href="{{ route('reports.indicators.pdf') }}@if($qs)?{{ $qs }}@endif"
           target="_blank"
           class="px-3 py-2 rounded-md bg-[var(--accent)] text-white hover:opacity-90">
          Print / PDF
        </a>
      </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4 rounded-lg border border-[var(--border)]"
          style="background:var(--card-bg);">
      <div>
        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Search</label>
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
               class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
               placeholder="objective, indicator, description…">
      </div>
      <div>
        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">DOST Agency</label>
        <input type="text" name="agency" value="{{ $filters['agency'] ?? '' }}"
               class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
               placeholder="e.g., DOST 1, STII">
      </div>
      <div>
        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Target Period</label>
        <input type="text" name="period" value="{{ $filters['period'] ?? '' }}"
               class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
               placeholder="e.g., Q4 2025">
      </div>
      <div class="flex items-end gap-2">
        <button class="px-3 py-2 rounded-md bg-[var(--accent)] text-white hover:opacity-90" type="submit">Apply</button>
        <a href="{{ route('reports.indicators') }}"
           class="px-3 py-2 rounded-md border border-[var(--border)] text-[var(--text)] hover:bg-[var(--bg)]">Reset</a>
      </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-[var(--border)]" style="background:var(--card-bg);">
      <table class="min-w-full text-sm">
        <thead class="text-xs uppercase text-[var(--text-muted)] border-b border-[var(--border)]">
          <tr class="[&>th]:px-4 [&>th]:py-3 text-left">
            <th>ID</th>
            <th>Objective / Result</th>
            <th>Indicator</th>
            <th>Description</th>
            <th>DOST Agency</th>
            <th>Baseline</th>
            <th>Accomp.</th>
            <th>Annual Target</th>
            <th>Target Period</th>
            <th>Target Value</th>
            <th>MOV</th>
            <th>Responsible</th>
            <th>Reporting</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          @forelse ($objectives as $o)
            <tr class="[&>td]:px-4 [&>td]:py-3 align-top">
              <td class="text-[var(--text-muted)]">{{ $o->id }}</td>
              <td class="font-medium text-[var(--text)]">{{ $o->objective_result }}</td>
              <td>{{ $o->indicator }}</td>
              <td class="max-w-[28ch] truncate" title="{{ $o->description }}">{{ $o->description }}</td>
              <td>{{ $o->dost_agency }}</td>
              <td>{{ $o->baseline }}</td>
              <td>{{ $o->accomplishments }}</td>
              <td>{{ $o->annual_plan_targets }}</td>
              <td>{{ $o->target_period }}</td>
              <td>{{ $o->target_value }}</td>
              <td class="max-w-[20ch] truncate" title="{{ $o->mov }}">{{ $o->mov }}</td>
              <td>{{ $o->responsible_agency }}</td>
              <td>{{ $o->reporting_agency }}</td>
              <td class="whitespace-nowrap text-[var(--text-muted)]">{{ optional($o->created_at)->format('Y-m-d') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="14" class="px-4 py-6 text-center text-[var(--text-muted)]">No results.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div>
      {{ $objectives->links() }}
    </div>
  </div>
</x-layouts.app>
