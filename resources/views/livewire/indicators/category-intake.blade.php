<section class="w-full intake-page">
  <style>
    /* Page‑scoped tweaks for better contrast and alignment */
    .intake-page .intake-toolbar { background-color: color-mix(in oklab, var(--card-bg) 80%, transparent); }
    .intake-page .intake-card    { background-color: color-mix(in oklab, var(--card-bg) 70%, transparent); }
    .intake-page .intake-overlay { background: rgba(0,0,0,.2); }
  </style>
  <div class="page-narrow space-y-6">
    <div class="border border-[var(--border)] intake-toolbar rounded-2xl px-6 py-5 shadow-sm flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-muted)]">Indicator Intake</p>
        <h1 class="text-3xl font-extrabold text-[var(--text)] leading-tight">
          {{-- **CHANGE HERE**: Always shows "Select Chapter" if grouping step is required --}}
          @if($this->shouldShowGrouping)
            Select Chapter
          @else
            Select Indicator
          @endif
        </h1>
        <p class="text-[var(--text-muted)] max-w-2xl mt-1">Pick a category, choose an indicator, review its details, and launch the “Add Information” form without leaving the page.</p>
      </div>
      @if (session()->has('success'))
        <div class="flex items-center gap-2 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5L20 7"/></svg>
          <span>{{ session('success') }}</span>
        </div>
      @endif
    </div>

    @if(!$category)
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($categories as $key => $label)
          <button
            wire:click="selectCategory('{{ $key }}')"
            class="rounded-2xl border border-[var(--border)] bg-[var(--card-bg)]/80 backdrop-blur px-5 py-4 text-left transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-[var(--accent)] focus-visible:outline-none"
            type="button"
          >
            <div class="flex items-center justify-end gap-2">
              <span class="text-[var(--text-muted)] text-xs">{{ strtoupper($key) }}</span>
            </div>
            <div class="mt-2 text-xl font-semibold text-[var(--text)]">{{ $label }}</div>
            <p class="mt-2 text-xs text-[var(--text-muted)] leading-relaxed">Jump into {{ $label }} indicators.</p>
          </button>
        @endforeach
      </div>
    @else
      {{-- MODIFIED: Changed layout to flex-col on mobile to prevent side-scrolling --}}
      <div class="sticky top-4 z-[5] rounded-2xl border border-[var(--border)] intake-toolbar px-4 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-3">
            <span class="text-lg font-semibold text-[var(--text)] truncate block">{{ $categories[$category] ?? \Illuminate\Support\Str::headline($category) }}</span>
            <button type="button" wire:click="selectCategory('')" class="text-xs text-blue-400 hover:underline shrink-0">Change</button>
          </div>
        </div>
        <div class="flex items-center gap-3 justify-end shrink-0 w-full sm:w-auto">
          {{-- Search removed per request --}}
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5">
        
        {{-- Generalized Grouping/Chapter Selection --}}
        @if($this->shouldShowGrouping)
          <div class="rounded-2xl border border-[var(--border)] intake-card shadow-sm p-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-[var(--text)]">
                {{-- **CHANGE HERE**: Always shows "Chapters" --}}
                Chapters
              </h3>
              @if(auth()->user()?->isSuperAdmin())
                <button type="button" class="text-xs rounded-full border px-3 py-1 hover:bg-[var(--bg)]" wire:click="openChapterModal">
                    {{-- **CHANGE HERE**: Always shows "Create Chapter" --}}
                    Create Chapter
                </button>
              @endif
            </div>
            <div class="divide-y divide-[var(--border)] mt-3">
              @forelse($this->groupings as $c)
                {{-- Use generic category, but pass the Chapter/Grouping ID --}}
                <a wire:navigate href="{{ route('objectives.index', ['category' => $category, 'chapter' => $c->id]) }}" class="block w-full text-left py-3 px-3 rounded hover:bg-[var(--bg)]">
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <div class="font-semibold text-[var(--text)]">{{ $c->title }}</div>
                      @if($c->description)
                        <div class="text-xs text-[var(--text-muted)] mt-1 line-clamp-2">{{ $c->description }}</div>
                      @endif
                    </div>
                  </div>
                </a>
              @empty
                <div class="py-6 text-center text-[var(--text-muted)] text-sm">No chapters yet.</div>
              @endforelse
            </div>
          </div>

          @if(auth()->user()?->isSuperAdmin())
            {{-- Chapter/Grouping Modal HTML --}}
            <div x-data="{ open: $wire.entangle('showChapterModal') }" x-cloak x-show="open" class="fixed inset-0 z-[9999]">
              <div class="absolute inset-0 bg-black/50" @click="open=false; $wire.cancelChapter()"></div>
              <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-xl border border-[var(--border)] bg-[var(--card-bg)] shadow-2xl">
                  <button class="absolute top-3 right-3 text-[var(--text-muted)] hover:text-[var(--text)]" @click="open=false; $wire.cancelChapter()" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                  <div class="p-5 text-sm leading-snug space-y-3">
                    <h3 class="text-lg font-semibold text-[var(--text)]">Create Chapter</h3>
                    <div>
                      <label class="block mb-1">Title</label>
                      <input type="text" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" wire:model.defer="ch_title" />
                      @error('ch_title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                      <label class="block mb-1">Description (optional)</label>
                      <textarea rows="2" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" wire:model.defer="ch_description"></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                      <button type="button" class="text-sm rounded border px-3 py-1.5" @click="open=false; $wire.cancelChapter()">Cancel</button>
                      {{-- **CHANGE HERE**: Always shows "Save Chapter" --}}
                      <button type="button" class="text-sm rounded border px-3 py-1.5 bg-[var(--accent)] text-[var(--accent-foreground)]" wire:click="saveChapter">Save Chapter</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif
        @endif
        
        {{-- Indicator list shows if NO grouping is needed OR if a grouping is selected --}}
        @if(!$this->shouldShowGrouping || $chapter)
        <div>
          <div class="rounded-2xl border border-[var(--border)] intake-card shadow-sm relative overflow-hidden">
            {{-- MODIFIED: Changed header to wrap on smaller screens --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between px-5 py-4 border-b border-[var(--border)]/60 gap-4">
              <div>
                <p class="text-sm font-semibold text-[var(--text)]">
                  {{ $category === 'pdp' ? 'Objectives / Results' : 'Indicator Library' }}
                </p>
                {{-- Dynamic display of selected grouping --}}
                <p class="text-xs text-[var(--text-muted)]">
                  Showing {{ count($this->list) }} templates 
                  @if($chapter) 
                    for: <span class="font-medium text-[var(--text)]">{{ optional(\App\Models\Chapter::find($chapter))->title }}</span>
                  @endif
                </p>
              </div>
              <div class="flex flex-wrap items-center gap-3">
                {{-- Dynamic Back button --}}
                @if($this->chapter)
                  <button type="button" class="text-xs rounded-full border px-3 py-1 hover:bg-[var(--bg)]" wire:click="selectChapter(null)">
                    Back to Chapters
                  </button>
                @endif
                @if(auth()->user()?->isSuperAdmin())
                  <flux:button
                    size="sm"
                    variant="primary"
                    class="rounded-full h-8 !px-3 inline-flex items-center shrink-0"
                    wire:click="startCreate"
                  >
                    <span class="flex items-center gap-2 whitespace-nowrap leading-none">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="M12 5v14"/>
                      </svg>
                      <span class="shrink-0">Create Indicator</span>
                    </span>
                  </flux:button>
                @endif
                <span class="text-xs text-[var(--text-muted)] uppercase tracking-wide">Sorted A - Z</span>
              </div>
            </div>
            <div wire:loading.class.remove="hidden" wire:loading.class="flex" class="absolute inset-0 intake-overlay backdrop-blur-[1px] z-10 items-center justify-center hidden">
              <svg class="animate-spin h-7 w-7 text-[var(--text)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            </div>
            <div class="divide-y divide-[var(--border)]/60 max-h-[70vh] overflow-y-auto">
              @forelse($this->list as $t)
                <a
                  wire:navigate href="{{ route('dashboard', ['indicator' => $t->name]) }}"
                  class="w-full text-left px-5 py-4 transition-all hover:bg-[var(--bg)]/40 flex flex-col sm:flex-row sm:items-start justify-between gap-4"
                >
                  <div class="min-w-0 flex-1">
                    <div class="text-xs uppercase tracking-wide text-[var(--text-muted)]">{{ $t->code }}</div>
                    <div class="text-base font-semibold text-[var(--text)] break-words">{{ $t->name }}</div>
                    @if($t->description)
                      <p class="text-xs text-[var(--text-muted)] line-clamp-2 mt-1">{{ $t->description }}</p>
                    @endif
                  </div>
                  <div class="text-left sm:text-right text-xs text-[var(--text-muted)] flex flex-row sm:flex-col items-center sm:items-end gap-2 sm:gap-1 shrink-0">
                    <span class="rounded-full border border-[var(--border)] px-2 py-0.5">{{ \Illuminate\Support\Str::upper($t->allowed_value_type) }}</span>
                    <span>{{ $t->category === 'pdp' ? 'PDP' : \Illuminate\Support\Str::headline($t->category ?: 'agency_specifics') }}</span>
                  </div>
                </a>
              @empty
                <div class="px-6 py-10 text-center text-[var(--text-muted)] space-y-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 5h18M3 12h18M3 19h18"/><path d="M7 8h.01M7 15h.01"/></svg>
                  <p class="font-semibold text-[var(--text)]">No indicators found</p>
                  <p class="text-sm">Refine the search or create a new template.</p>
                </div>
              @endforelse
            </div>
          </div>
        </div>
        @endif

        </div>
    @endif
  </div>

  {{-- ... Objective Form Modal HTML ... --}}

  @if(auth()->user()?->isSuperAdmin())
    <div x-data="{ open: $wire.entangle('showCreate') }" x-cloak x-show="open" class="fixed inset-0 z-[9999]">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open=false; $wire.cancelCreate()"></div>
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg rounded-xl border border-[var(--border)] bg-[var(--card-bg)] shadow-2xl">
          <button class="absolute top-3 right-3 text-[var(--text-muted)] hover:text-[var(--text)]" @click="open=false; $wire.cancelCreate()" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <div class="p-5 text-sm leading-snug">
            <h3 class="text-lg font-semibold text-[var(--text)] mb-1">Create Indicator</h3>
            <p class="text-xs text-[var(--text-muted)] mb-4">This template will be created under <span class="font-semibold">{{ $categories[$category] ?? $category }}</span>.@if($chapter) It will be assigned to **{{ optional(\App\Models\Chapter::find($chapter))->title }}** chapter. @endif</p>

            <div class="space-y-3">
              <div>
                <label class="block mb-1">Code</label>
                <input type="text" name="code" id="create-code" wire:model.defer="code" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
                @error('code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="block mb-1">Name</label>
                <input type="text" name="name" id="create-name" wire:model.defer="name" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="block mb-1">Description</label>
                <textarea name="description" id="create-description" wire:model.defer="description" rows="3" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"></textarea>
              </div>
              <div>
                <label class="block mb-1">Value Type</label>
                <select name="allowed_value_type" id="create-allowed-type" wire:model.defer="allowed_value_type" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5">
                  <option value="value">Value</option>
                  <option value="percent">Percent</option>
                  <option value="count">Count</option>
                  <option value="currency">Currency</option>
                </select>
                <p class="text-[var(--text-muted)] text-xs mt-1">Percent = 0–100 with decimals; Count = whole numbers; Currency = money with 2 decimals; Value = generic number.</p>
              </div>
              <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-2" for="create-baseline-required"><input id="create-baseline-required" name="baseline_required" type="checkbox" wire:model.defer="baseline_required" /> Baseline required</label>
                <label class="inline-flex items-center gap-2" for="create-mov-required"><input id="create-mov-required" name="mov_required" type="checkbox" wire:model.defer="mov_required" /> MOV required</label>
                <label class="inline-flex items-center gap-2" for="create-is-active"><input id="create-is-active" name="is_active" type="checkbox" wire:model.defer="is_active" /> Active</label>
              </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
              <flux:button size="sm" variant="outline" @click="open=false; $wire.cancelCreate()">Cancel</flux:button>
              <flux:button size="sm" variant="primary" wire:click="saveIndicator">Save Indicator</flux:button>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

</section>
