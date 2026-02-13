<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-xl font-bold text-gray-800">Strategic Plan Manager</h2>
        <p class="text-xs text-gray-500">Manage Pillar, Outcome, and Strategy numeric values</p>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex border-b border-gray-200">
            <button wire:click="setTab('pillars')"
                    class="flex-1 px-6 py-4 text-sm font-bold uppercase transition {{ $activeTab === 'pillars' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                Pillars
            </button>
            <button wire:click="setTab('outcomes')"
                    class="flex-1 px-6 py-4 text-sm font-bold uppercase transition {{ $activeTab === 'outcomes' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                Outcomes
            </button>
            <button wire:click="setTab('strategies')"
                    class="flex-1 px-6 py-4 text-sm font-bold uppercase transition {{ $activeTab === 'strategies' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                Strategies
            </button>
        </div>

        {{-- Pillars Tab --}}
        @if($activeTab === 'pillars')
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-700">Pillars</h3>
                    <button wire:click="createPillar" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition">
                        + Add Pillar
                    </button>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-bold text-gray-600">Value</th>
                                <th class="px-4 py-3 font-bold text-gray-600">Status</th>
                                <th class="px-4 py-3 font-bold text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($pillars as $pillar)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ $pillar->value }}</td>
                                    <td class="px-4 py-3">
                                        @if($pillar->is_active)
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase">Active</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-bold uppercase">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button wire:click="editPillar({{ $pillar->id }})" class="text-blue-600 hover:underline font-bold text-xs uppercase">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="deletePillar({{ $pillar->id }})" class="text-red-600 hover:underline font-bold text-xs uppercase">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-10 text-center text-gray-400 italic">No pillars found. Create your first pillar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-4 border-t bg-gray-50/50">
                        {{ $pillars->links() }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Outcomes Tab --}}
        @if($activeTab === 'outcomes')
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-700">Outcomes</h3>
                    <button wire:click="createOutcome" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition">
                        + Add Outcome
                    </button>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-bold text-gray-600">Value</th>
                                <th class="px-4 py-3 font-bold text-gray-600">Status</th>
                                <th class="px-4 py-3 font-bold text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($outcomes as $outcome)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ $outcome->value }}</td>
                                    <td class="px-4 py-3">
                                        @if($outcome->is_active)
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase">Active</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-bold uppercase">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button wire:click="editOutcome({{ $outcome->id }})" class="text-blue-600 hover:underline font-bold text-xs uppercase">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="deleteOutcome({{ $outcome->id }})" class="text-red-600 hover:underline font-bold text-xs uppercase">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-10 text-center text-gray-400 italic">No outcomes found. Create your first outcome.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-4 border-t bg-gray-50/50">
                        {{ $outcomes->links() }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Strategies Tab --}}
        @if($activeTab === 'strategies')
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-700">Strategies</h3>
                    <button wire:click="createStrategy" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition">
                        + Add Strategy
                    </button>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-bold text-gray-600">Value</th>
                                <th class="px-4 py-3 font-bold text-gray-600">Status</th>
                                <th class="px-4 py-3 font-bold text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($strategies as $strategy)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ $strategy->value }}</td>
                                    <td class="px-4 py-3">
                                        @if($strategy->is_active)
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase">Active</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-bold uppercase">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button wire:click="editStrategy({{ $strategy->id }})" class="text-blue-600 hover:underline font-bold text-xs uppercase">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="deleteStrategy({{ $strategy->id }})" class="text-red-600 hover:underline font-bold text-xs uppercase">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-10 text-center text-gray-400 italic">No strategies found. Create your first strategy.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-4 border-t bg-gray-50/50">
                        {{ $strategies->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Pillar Modal --}}
    @if($showPillarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-800 mb-6 border-b pb-2">
                    {{ $editingPillarId ? 'Edit Pillar' : 'New Pillar' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Numeric Value</label>
                        <input type="number" wire:model="pillarValue" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="100">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="pillarIsActive" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-3 pt-4 border-t">
                    <button wire:click="closePillarModal" class="px-4 py-2 text-xs font-bold uppercase text-gray-400 hover:text-gray-600">Cancel</button>
                    <button wire:click="savePillar" class="px-6 py-2 bg-blue-600 text-white text-xs font-bold uppercase rounded-lg shadow-md">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Outcome Modal --}}
    @if($showOutcomeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-800 mb-6 border-b pb-2">
                    {{ $editingOutcomeId ? 'Edit Outcome' : 'New Outcome' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Numeric Value</label>
                        <input type="number" wire:model="outcomeValue" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="50">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="outcomeIsActive" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-3 pt-4 border-t">
                    <button wire:click="closeOutcomeModal" class="px-4 py-2 text-xs font-bold uppercase text-gray-400 hover:text-gray-600">Cancel</button>
                    <button wire:click="saveOutcome" class="px-6 py-2 bg-blue-600 text-white text-xs font-bold uppercase rounded-lg shadow-md">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Strategy Modal --}}
    @if($showStrategyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-800 mb-6 border-b pb-2">
                    {{ $editingStrategyId ? 'Edit Strategy' : 'New Strategy' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Numeric Value</label>
                        <input type="number" wire:model="strategyValue" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="10">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="strategyIsActive" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-3 pt-4 border-t">
                    <button wire:click="closeStrategyModal" class="px-4 py-2 text-xs font-bold uppercase text-gray-400 hover:text-gray-600">Cancel</button>
                    <button wire:click="saveStrategy" class="px-6 py-2 bg-blue-600 text-white text-xs font-bold uppercase rounded-lg shadow-md">Save</button>
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

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="fixed bottom-4 right-4 bg-red-600 text-white px-6 py-4 rounded-xl shadow-xl font-bold text-xs uppercase max-w-md">
            <div class="mb-2">Please fix the following errors:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
