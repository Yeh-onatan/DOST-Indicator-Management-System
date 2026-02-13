<div class="p-6 space-y-6">
    {{-- Header with visible Blue Button --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Region Management</h2>
            <p class="text-xs text-gray-500">Manage DOST Regional Jurisdictions</p>
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto">
            <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search regions..." 
                   class="w-full md:w-64 px-4 py-2 text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
            
            <button wire:click="create" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-md transition whitespace-nowrap">
                + Add Region
            </button>
        </div>
    </div>

    {{-- Table List --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-bold text-gray-600">Order</th>
                    <th class="px-6 py-3 font-bold text-gray-600">Code</th>
                    <th class="px-6 py-3 font-bold text-gray-600">Name</th>
                    <th class="px-6 py-3 font-bold text-gray-600">Head of Office (Regional)</th>
                    <th class="px-6 py-3 font-bold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($regions as $region)
                    <tr class="hover:bg-gray-50 transition" wire:key="region-{{ $region->id }}">
                        <td class="px-6 py-4 font-mono text-center text-gray-500">{{ $region->order_index }}</td>
                        <td class="px-6 py-4 font-bold text-blue-600">{{ $region->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $region->name }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">
                            {{ $region->director->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="edit({{ $region->id }})" class="text-blue-600 hover:underline font-bold text-xs uppercase">Edit</button>
                            <span class="text-gray-300">|</span>
                            <button wire:click="openAssignments({{ $region->id }})" class="text-indigo-600 hover:underline font-bold text-xs uppercase">Assign Offices</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-gray-400 italic">No regions found matching your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t bg-gray-50/50">
            {{ $regions->links() }}
        </div>
    </div>

    {{-- CREATE/EDIT MODAL --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl animate-in zoom-in duration-200">
            <h3 class="text-lg font-bold text-gray-800 mb-6 border-b pb-2">
                {{ $editingId ? 'Edit Region' : 'New Region' }}
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Sort Order</label>
                        <input type="number" min="0" wire:model="order_index" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Region Code</label>
                        <input type="text" wire:model="code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="NCR">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Full Name</label>
                    <input type="text" wire:model="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Head of Office (Regional)</label>
                    <select wire:model="director_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Head of Office --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3 pt-4 border-t">
                <button wire:click="closeModal" class="px-4 py-2 text-xs font-bold uppercase text-gray-400 hover:text-gray-600">Cancel</button>
                <button wire:click="save" class="px-6 py-2 bg-blue-600 text-white text-xs font-bold uppercase rounded-lg shadow-md">Save Changes</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ASSIGNMENT MODAL (The missing button functionality) --}}
    @if($showAssignmentModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-2xl flex flex-col max-h-[85vh]">
            <div class="mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-gray-800">Assign Offices to {{ $selectedRegion?->name }}</h3>
                <p class="text-xs text-gray-500">Select which offices report to this regional branch.</p>
            </div>

            {{-- Scrollable Checkbox List --}}
            <div class="flex-1 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 space-y-1">
                @foreach($availableOffices as $office)
                    <label class="flex items-center space-x-3 p-3 hover:bg-white hover:shadow-sm rounded-lg cursor-pointer transition group border border-transparent hover:border-gray-200">
                        <input type="checkbox" 
                               value="{{ $office->id }}" 
                               wire:model="selectedOffices"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-gray-700">{{ $office->name }}</span>
                            <span class="text-[10px] text-gray-400 uppercase font-mono">{{ $office->code }}</span>
                            @if($office->region_id && $office->region_id != $selectedRegion?->id)
                                <span class="text-[9px] text-orange-500 font-bold uppercase mt-0.5 italic">Currently in Region ID: {{ $office->region_id }}</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                <button wire:click="$set('showAssignmentModal', false)" class="px-4 py-2 text-xs font-bold uppercase text-gray-400 hover:text-gray-600">Cancel</button>
                <button wire:click="saveAssignments" class="px-6 py-2 bg-blue-600 text-white text-xs font-bold uppercase rounded-lg shadow-md">Update Assignments</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-xl shadow-xl font-bold text-xs uppercase animate-bounce">
            {{ session('message') }}
        </div>
    @endif
</div>