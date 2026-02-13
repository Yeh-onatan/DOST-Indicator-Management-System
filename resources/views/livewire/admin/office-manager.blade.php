<div class="p-6 space-y-6">
    {{-- Header Section: Title, Search, and Create Button --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Office Management</h2>
            <p class="text-xs text-gray-500 mt-1">Configure DOST organizational hierarchy</p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            {{-- Search Bar --}}
            <div class="relative flex-1 md:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       wire:model.live.debounce.500ms="search" 
                       placeholder="Search name or code..." 
                       class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>

            {{-- The Create Button (Forced Blue for Visibility) --}}
            <button wire:click="create" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-bold transition shadow-md whitespace-nowrap flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Office
            </button>
        </div>
    </div>

    {{-- Table Container --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase tracking-wider">Office Name</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase tracking-wider">Region</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($offices as $office)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition" wire:key="office-{{ $office->id }}">
                        <td class="px-6 py-4 font-mono text-xs text-blue-600 dark:text-blue-400 font-bold">{{ $office->code }}</td>
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $office->name }}</div>
                            @if($office->parent)
                                <div class="text-[10px] text-gray-500">Reports to: {{ $office->parent->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                {{ $office->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($office->region)
                                <span class="bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-xs px-2 py-1 rounded border border-indigo-100 dark:border-indigo-800 uppercase font-semibold">
                                    {{ $office->region->code }}
                                </span>
                            @else
                                <span class="text-gray-400 italic text-xs">National</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button wire:click="edit({{ $office->id }})" class="text-blue-600 hover:underline font-bold text-xs uppercase">Edit</button>
                            <button wire:click="delete({{ $office->id }})" 
                                    wire:confirm="Permanent deletion: Are you sure?"
                                    class="text-red-500 hover:text-red-700 font-bold text-xs uppercase">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                            No offices found. Try a different search term.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
            {{ $offices->links() }}
        </div>
    </div>

    {{-- Modal Form (Using @if to save RAM) --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl w-full max-w-md p-6 shadow-2xl my-8"
             wire:click.away="closeModal">
            
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b dark:border-gray-700 pb-3">
                {{ $editingId ? 'Edit Office Details' : 'Register New Office' }}
            </h3>

            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Office Code</label>
                        <input type="text" wire:model="code" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        @error('code') <span class="text-red-500 text-[10px] italic">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Office Type</label>
                        <select wire:model="type" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm outline-none">
                            <option value="HO">Head Office</option>
                            <option value="RO">Regional Office</option>
                            <option value="PSTO">Provincial/Cluster</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Full Office Name</label>
                    <input type="text" wire:model="name" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    @error('name') <span class="text-red-500 text-[10px] italic">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Parent (Reports To)</label>
                    <select wire:model="parent_office_id" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm outline-none">
                        <option value="">-- No Parent (Top Tier) --</option>
                        @foreach($parentOptions as $option)
                            @if($option->id != $editingId)
                                <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->type }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t dark:border-gray-700 pt-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Region</label>
                        <select wire:model="region_id" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm outline-none">
                            <option value="">-- National --</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Head of Office</label>
                        <select wire:model="head_user_id" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm outline-none">
                            <option value="">-- Unassigned --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-4 border-t dark:border-gray-700">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-bold uppercase text-gray-500 hover:text-gray-700">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase rounded-xl transition shadow-lg">
                        Save Office
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>