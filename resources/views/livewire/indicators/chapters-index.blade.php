<section class="w-full intake-page">
  <style>
    .intake-page .intake-toolbar { background-color: color-mix(in oklab, var(--card-bg) 80%, transparent); }
    .intake-page .intake-card    { background-color: color-mix(in oklab, var(--card-bg) 70%, transparent); }
  </style>

  <div class="page-narrow space-y-6">
    {{-- Strategic Plan is now handled in its own page; this view will be skipped by redirect in the component. --}}

    <div class="rounded-2xl border border-[var(--border)] intake-card shadow-sm p-4">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-semibold text-[var(--text)]">Outcomes</h3>
          <span class="text-[10px] px-2 py-0.5 rounded-full border border-[var(--border)] text-[var(--text-muted)]">{{ $category === 'pdp' ? 'PDP' : \Illuminate\Support\Str::headline($category) }}</span>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('dashboard') }}" class="h-8 text-sm rounded-full px-3 inline-flex items-center justify-center border border-[var(--border)] hover:bg-[var(--bg)] text-[var(--text)]">
            Home
          </a>
          @if(auth()->user()?->isSuperAdmin())
            <button type="button"
                    wire:click="toggleManage"
                    class="h-8 px-3 rounded-lg border border-[var(--border)] bg-[var(--card-bg)] text-xs font-semibold text-[var(--text)] hover:bg-[var(--bg)] transition">
              {{ $manage ? 'Exit Manage' : 'Manage' }}
            </button>
            @if($manage)
              <button type="button"
                      wire:click="startCreate"
                      class="h-8 px-3 rounded-lg bg-[var(--color-accent)] text-[var(--color-accent-foreground)] text-xs font-semibold shadow-sm hover:opacity-90 transition">
                + Create Chapter
              </button>
            @endif
          </div>
        @endif
      </div>
      <div class="divide-y divide-[var(--border)] mt-3">
        @forelse($this->list as $c)
          <div class="relative w-full py-3 px-3 rounded hover:bg-[var(--bg)]">
            @if(!$manage)
              <a href="{{ route('objectives.index', ['category' => $category, 'chapter' => $c->id]) }}" class="absolute inset-0" aria-label="Open chapter {{ $c->title }}"></a>
            @endif
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <div class="font-semibold text-[var(--text)] truncate">{{ $c->title }}</div>
                @if($c->description)
                  <div class="text-xs text-[var(--text-muted)] mt-1 line-clamp-2">{{ $c->description }}</div>
                @endif
              </div>
              @if(auth()->user()?->isSuperAdmin() && $manage)
                <div class="flex items-center gap-2 shrink-0 relative z-10">
                  <button type="button" wire:click="startEdit({{ $c->id }})" class="inline-flex items-center gap-1 rounded-lg border border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5 text-xs font-semibold text-[var(--text)] hover:bg-[var(--bg)] transition">Edit</button>
                  <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Delete this chapter?" class="inline-flex items-center gap-1 rounded-lg border border-red-500/70 bg-[var(--card-bg)] px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">Delete</button>
                </div>
              @endif
            </div>
          </div>
        @empty
          <div class="py-6 text-center text-[var(--text-muted)] text-sm">No outcomes yet.</div>
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
          <h3 class="text-lg font-semibold text-[var(--text)] mb-1">Create Chapter</h3>
          <p class="text-xs text-[var(--text-muted)] mb-4">Category: <span class="font-semibold">{{ \Illuminate\Support\Str::headline($category) }}</span></p>

          <div class="space-y-3">
            <div>
              <label class="block mb-1">Title <span class="text-red-500">*</span></label>
              <input type="text" name="title" wire:model.defer="title" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
              @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block mb-1">Code</label>
              <input type="text" name="code" wire:model.defer="code" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
            </div>
            <div>
              <label class="block mb-1">Description</label>
              <textarea name="description" wire:model.defer="description" rows="3" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
              <div>
                <label class="block mb-1">Sort Order</label>
                <input type="number" min="0" name="sort_order" wire:model.defer="sort_order" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
              </div>
              <label class="inline-flex items-center gap-2 mt-2 sm:mt-0"><input type="checkbox" wire:model.defer="is_active"/> Active</label>
            </div>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="h-9 text-sm rounded-lg px-3 inline-flex items-center justify-center border border-[var(--border)] bg-[var(--card-bg)] text-[var(--text)] hover:bg-[var(--bg)] transition" @click="open=false; $wire.cancelCreate()">Cancel</button>
            <button type="button" class="h-9 text-sm rounded-lg px-3 inline-flex items-center justify-center bg-[var(--color-accent)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-sm hover:opacity-90 transition" wire:click="saveChapter">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if(auth()->user()?->isSuperAdmin())
  <div x-data="{ open: $wire.entangle('showEdit') }" x-cloak x-show="open" class="fixed inset-0 z-[9999]">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open=false; $wire.cancelEdit()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative w-full max-w-lg rounded-xl border border-[var(--border)] bg-[var(--card-bg)] shadow-2xl">
        <button class="absolute top-3 right-3 text-[var(--text-muted)] hover:text-[var(--text)]" @click="open=false; $wire.cancelEdit()" aria-label="Close">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="p-5 text-sm leading-snug">
          <h3 class="text-lg font-semibold text-[var(--text)] mb-1">Edit Chapter</h3>
          <p class="text-xs text-[var(--text-muted)] mb-4">Category: <span class="font-semibold">{{ \Illuminate\Support\Str::headline($category) }}</span></p>

          <div class="space-y-3">
            <div>
              <label class="block mb-1">Title <span class="text-red-500">*</span></label>
              <input type="text" name="title" wire:model.defer="title" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
              @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block mb-1">Code</label>
              <input type="text" name="code" wire:model.defer="code" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
            </div>
            <div>
              <label class="block mb-1">Description</label>
              <textarea name="description" wire:model.defer="description" rows="3" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
              <div>
                <label class="block mb-1">Sort Order</label>
                <input type="number" min="0" name="sort_order" wire:model.defer="sort_order" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5" />
              </div>
              <label class="inline-flex items-center gap-2 mt-2 sm:mt-0"><input type="checkbox" wire:model.defer="is_active"/> Active</label>
            </div>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="h-9 text-sm rounded-lg px-3 inline-flex items-center justify-center border border-[var(--border)] bg-[var(--card-bg)] text-[var(--text)] hover:bg-[var(--bg)] transition" @click="open=false; $wire.cancelEdit()">Cancel</button>
            <button type="button" class="h-9 text-sm rounded-lg px-3 inline-flex items-center justify-center bg-[var(--color-accent)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-sm hover:opacity-90 transition" wire:click="updateChapter">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
</section>
