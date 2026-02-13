<section class="w-full">
    <div class="relative mb-6 w-full page-narrow">
        <h1 class="text-2xl font-extrabold text-[var(--text)]">Create Account</h1>
        <p class="text-[var(--text-muted)]">Super Admin can create users with a temporary password</p>
        <flux:separator variant="subtle" />
    </div>
    <div class="page-narrow">
        @if (session()->has('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-100">
                Please fix the highlighted fields below.
            </div>
        @endif

        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4 my-6 text-sm leading-snug">
            <div class="md:col-span-2">
                <label class="block text-xs mb-1">Full name</label>
                <input type="text" wire:model.defer="name" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"/>
                @error('name')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs mb-1">Username</label>
                <input type="text" wire:model.defer="username" placeholder="letters, numbers, dashes or underscores" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"/>
                @error('username')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs mb-1">Email</label>
                <input type="email" wire:model.defer="email" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"/>
                @error('email')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs mb-1">Role</label>
                <select wire:model.defer="role" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5">
                    <option value="super_admin">Super Admin</option>
                    <option value="administrator">Admin</option>
                    <option value="head_officer">Head Officer (H.O)</option>
                    <option value="ro">RO (Regional Office)</option>
                    <option value="psto">PSTO (Provincial S&amp;T Office)</option>
                    <option value="proponent">Proponent</option>
                </select>
                @error('role')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2" x-data="{copied:false}">
                <label class="block text-xs mb-1">Temporary password</label>
                <div class="flex gap-2 items-center">
                    <input x-ref="pwd" type="text" readonly value="{{ $generated_password }}" class="flex-1 rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5"/>
                    <flux:button type="button" variant="outline" wire:click="generatePassword" class="px-3 py-1.5">Generate</flux:button>
                    <flux:button
                        type="button" variant="outline"
                        @click="
                            navigator.clipboard.writeText($refs.pwd.value)
                              .then(() => { copied = true; setTimeout(()=>copied=false, 1500); })
                              .catch(() => { copied = true; setTimeout(()=>copied=false, 1500); });
                        "
                        class="px-3 py-1.5"
                    >Copy</flux:button>
                </div>
                <div x-show="copied" x-transition.opacity class="mt-2 text-xs text-green-700 dark:text-green-300" role="status" aria-live="polite">
                    Password copied to clipboard
                </div>
                <p class="text-xs text-[var(--text-muted)] mt-1">Share this once. The user can change it after login.</p>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <flux:button type="submit" variant="primary" class="px-4 py-2">Create Account</flux:button>
            </div>
        </form>
    </div>
</section>
