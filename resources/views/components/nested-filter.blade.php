@props(['label' => 'Filter', 'options' => [], 'wireModel' => 'filter', 'icon' => null, 'showSelected' => true, 'searchable' => false])

@php
    $optionsJson = json_encode($options);
@endphp

<div x-data="{
    open: false,
    selectedValue: @entangle($wireModel),
    options: {{ $optionsJson }},
    searchQuery: '',
    defaultLabel: '{{ $label }}',
    displayLabel: function() {
        const option = Object.entries(this.options).find(([value, label]) => value === this.selectedValue);
        return option ? option[1] : this.defaultLabel;
    },
    filteredOptions: function() {
        if (!this.searchQuery || this.searchQuery.trim() === '') {
            return Object.entries(this.options);
        }
        const query = this.searchQuery.toLowerCase().trim();
        return Object.entries(this.options).filter(([value, label]) => {
            return label.toString().toLowerCase().includes(query) || value.toString().toLowerCase().includes(query);
        });
    }
}" class="relative">
    {{-- Main Filter Button --}}
    <button
        @click="open = !open"
        @click.outside="open = false"
        class="h-9 px-4 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50 transition flex items-center gap-2 min-w-[120px] justify-between"
    >
        <div class="flex items-center gap-2 truncate">
            @if($icon)
                {!! $icon !!}
            @endif
            <span x-text="displayLabel()"></span>
        </div>
        <svg x-cloak x-show="!open" class="w-4 h-4 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
        <svg x-cloak x-show="open" class="w-4 h-4 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 min-w-[200px] max-w-sm rounded-lg shadow-lg bg-white border border-gray-200"
        style="display: none;"
    >
        @if($searchable)
            {{-- Search Input --}}
            <div class="p-3 border-b border-gray-200">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Search values..."
                        class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
            </div>
        @endif

        <div class="p-1 max-h-64 overflow-auto">
            @foreach($options as $value => $label)
                <button
                    wire:click="$set('{{ $wireModel }}', '{{ $value }}')"
                    @click="$wire.set('{{ $wireModel }}', '{{ $value }}'); open = false; searchQuery = ''"
                    :class="selectedValue === '{{ $value }}' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'"
                    x-show="filteredOptions().some(([v, l]) => v === '{{ $value }}')"
                    class="w-full text-left px-3 py-2 rounded-md text-sm transition truncate"
                >
                    {{ $label }}
                </button>
            @endforeach
            <template x-if="filteredOptions().length === 0">
                <div class="px-3 py-2 text-sm text-gray-500 text-center">No results found</div>
            </template>
        </div>
    </div>
</div>
