<section class="w-full intake-page">
  <style>
    .intake-page .intake-toolbar { background-color: color-mix(in oklab, var(--card-bg) 80%, transparent); }
    .intake-page .intake-card    { background-color: color-mix(in oklab, var(--card-bg) 70%, transparent); }
    .intake-page .intake-overlay { background: rgba(0,0,0,.2); }
  </style>
  <div class="page-narrow space-y-6">
    <div class="border border-[var(--border)] intake-toolbar rounded-2xl px-6 py-5 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-muted)]">Indicators</p>
          <h1 class="text-2xl font-extrabold text-[var(--text)] leading-tight">
            {{ $category === 'pdp' ? 'PDP' : \Illuminate\Support\Str::headline($category ?: 'Indicators') }}
            @if($chapterModel)
              <span class="text-sm font-normal text-[var(--text-muted)]">— Outcome: {{ $chapterModel->title }}</span>
            @endif
          </h1>
        </div>
        <div class="flex items-center gap-3">
          @if(in_array($category, ['strategic_plan','pdp','prexc','agency_specifics']))
            <a href="{{ route('chapters.index', ['category' => $category]) }}" class="text-xs rounded-full border px-3 py-1 hover:bg-[var(--bg)]">Back to Outcomes</a>
          @endif
          @if(auth()->user()?->isSuperAdmin())
            <button type="button" class="text-xs rounded-full border px-3 py-1 hover:bg-[var(--bg)]" wire:click="toggleManage">
              {{ $manage ? 'Done' : 'Manage' }}
            </button>
            @if($manage)
              <button type="button"
                      class="h-8 text-sm rounded-full px-3 inline-flex items-center justify-center bg-[var(--color-accent)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0"
                      wire:click="startCreate">+ Create Indicator</button>
            @endif
          @endif
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="flex-1 max-w-md">
          <input
            type="text"
            wire:model.live="search"
            placeholder="Search indicators by name, code, or description..."
            class="w-full rounded-lg border border-[var(--border)] bg-[var(--card-bg)] px-4 py-2 text-sm"
          />
        </div>
        @if($search)
          <button
            type="button"
            wire:click="$set('search', '')"
            class="text-xs text-[var(--text-muted)] hover:text-[var(--text)]"
          >
            Clear
          </button>
        @endif
      </div>
    </div>

    <div class="rounded-2xl border border-[var(--border)] intake-card shadow-sm overflow-hidden">
      <div class="divide-y divide-[var(--border)]/60">
        @forelse($this->list as $t)
          <div class="relative px-5 py-4 hover:bg-[var(--bg)]/40">
            @if(!$manage)
              <a href="{{ route('dashboard', ['chapter' => $chapter, 'indicator' => $t->name]) }}" class="absolute inset-0" aria-label="Open {{ $t->name }}"></a>
            @endif
            <div class="flex items-start justify-between gap-4">
              <div class="block min-w-0 flex-1">
                <div class="text-xs uppercase tracking-wide text-[var(--text-muted)]">{{ $t->code }}</div>
                <div class="text-base font-semibold text-[var(--text)] truncate">{{ $t->name }}</div>
                @if($t->description)
                  <p class="text-xs text-[var(--text-muted)] line-clamp-2 mt-1">{{ $t->description }}</p>
                @endif
              </div>
              <div class="text-right text-xs text-[var(--text-muted)] flex flex-col items-end gap-1 shrink-0">
                <span class="rounded-full border border-[var(--border)] px-2 py-0.5">{{ \Illuminate\Support\Str::upper($t->allowed_value_type) }}</span>
                <span>{{ $t->category === 'pdp' ? 'PDP' : \Illuminate\Support\Str::headline($t->category ?: 'agency_specifics') }}</span>
                @if(auth()->user()?->isSuperAdmin() && $manage)
                  <div class="flex items-center gap-2 relative z-10">
                    <button type="button" wire:click.stop="startEdit({{ $t->id }})" class="mt-1 text-yellow-600 hover:text-yellow-800 border border-[var(--border)] rounded px-2 py-0.5">Edit</button>
                    <button type="button" wire:click.stop="deleteTemplate({{ $t->id }})" wire:confirm="Delete this indicator?" class="mt-1 text-red-600 hover:text-red-800 border border-[var(--border)] rounded px-2 py-0.5">Delete</button>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div class="px-6 py-10 text-center text-[var(--text-muted)] space-y-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 5h18M3 12h18M3 19h18"/><path d="M7 8h.01M7 15h.01"/></svg>
            <p class="font-semibold text-[var(--text)]">No indicators found</p>
            <p class="text-sm">No templates under this scope.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>
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
          <p class="text-xs text-[var(--text-muted)] mb-4">This template will be created under <span class="font-semibold">{{ $category === 'pdp' ? 'PDP' : \Illuminate\Support\Str::headline($category ?: 'Indicators') }}</span>@if($chapter) and assigned to <span class="font-semibold">{{ optional($chapterModel)->title }}</span>@endif.</p>

          <div class="space-y-3">
            <div>
              <label class="block mb-1">Code</label>
              <input type="text" name="code" wire:model.defer="code" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" placeholder="Optional" />
              @error('code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block mb-1">Name</label>
              <input type="text" name="name" wire:model.defer="name" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
              @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            {{-- Value Type removed from this modal; defaults to generic value and is handled when adding information --}}
            <div></div>
            <div class="flex items-center gap-4">
              <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.defer="is_active"/> Active</label>
            </div>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="h-8 text-sm rounded-full px-3 inline-flex items-center justify-center border border-[var(--border)]" @click="open=false; $wire.cancelCreate()">Cancel</button>
            <button type="button" class="h-8 text-sm rounded-full px-3 inline-flex items-center justify-center bg-[var(--color-accent)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0" wire:click="saveIndicator">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div x-data="{ open: $wire.entangle('showEdit') }" x-cloak x-show="open" class="fixed inset-0 z-[9999]">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open=false; $wire.cancelEdit()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative w-full max-w-lg rounded-xl border border-[var(--border)] bg-[var(--card-bg)] shadow-2xl">
        <button class="absolute top-3 right-3 text-[var(--text-muted)] hover:text-[var(--text)]" @click="open=false; $wire.cancelEdit()" aria-label="Close">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="p-5 text-sm leading-snug">
          <h3 class="text-lg font-semibold text-[var(--text)] mb-1">Edit Indicator</h3>
          <div class="space-y-3">
            <div>
              <label class="block mb-1">Code</label>
              <input type="text" name="code" wire:model.defer="code" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" placeholder="Optional" />
              @error('code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block mb-1">Name</label>
              <input type="text" name="name" wire:model.defer="name" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
              @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            {{-- Value Type removed from this modal; it stays as-is on existing templates --}}
            <div></div>
            <div class="flex items-center gap-4">
              <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.defer="is_active"/> Active</label>
            </div>
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="h-8 text-sm rounded-full px-3 inline-flex items-center justify-center border border-[var(--border)]" @click="open=false; $wire.cancelEdit()">Cancel</button>
            <button type="button" class="h-8 text-sm rounded-full px-3 inline-flex items-center justify-center bg-[var(--color-accent)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0" wire:click="updateTemplate">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
</section>

