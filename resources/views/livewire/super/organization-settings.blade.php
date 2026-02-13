<section class="w-full">
    @include('partials.settings-heading')
    <x-settings.layout :heading="__('Organization Settings')" :subheading="__('System identity, timezone, and retention')">
        @if (session()->has('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ session('success') }}</div>
        @endif
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4 my-6">
            <div>
                <label class="block text-sm mb-1">System name</label>
                <input type="text" wire:model.defer="org_name" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]"/>
            </div>
            <div>
                <label class="block text-sm mb-1">Logo path/URL</label>
                <input type="text" wire:model.defer="org_logo_path" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]"/>
            </div>
            <div>
                <label class="block text-sm mb-1">Theme accent (hex or token)</label>
                <input type="text" wire:model.defer="theme_accent" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]" placeholder="#02aeef"/>
            </div>
            <div>
                <label class="block text-sm mb-1">Timezone</label>
                <input type="text" wire:model.defer="timezone" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]" placeholder="Asia/Manila"/>
            </div>
            <div>
                <label class="block text-sm mb-1">Locale</label>
                <input type="text" wire:model.defer="locale" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]" placeholder="en"/>
            </div>
            <div>
                <label class="block text-sm mb-1">Archive after N years</label>
                <input type="number" min="0" wire:model.defer="archive_years" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]"/>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Regions to roles mapping (JSON)</label>
                <textarea wire:model.defer="regions_roles" rows="3" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]" placeholder='{"NCR":"administrator","Region I":"proponent"}'></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Compliance policies (JSON)</label>
                <textarea wire:model.defer="compliance" rows="3" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]" placeholder='{"notification_retention_days": 90}'></textarea>
            </div>
            <div class="md:col-span-2">
                <button class="px-4 py-2 rounded bg-[#02aeef] text-white">Save</button>
            </div>
        </form>
    </x-settings.layout>
</section>

