@props([
    'show' => false,
    'viewMode' => false,
    'isUpdateProgress' => false,
    'adminBypassMode' => false,
    'editingQuickFormId' => null,
    'indicatorCategories' => null,
    'dynamicFields' => null,
    'pillars' => null,
    'outcomes' => null,
    'strategies' => null,
    'quickForm' => null,
    'dynamicValues' => null,
    'breakdown' => null,
    'chartData' => null,
    'indicatorHistory' => null,
    'indicatorProofs' => null,
])

@if($show)
<div x-data="{
    scrollToError() {
        setTimeout(() => {
            const firstError = this.$el.querySelector('.text-red-600');
            if (firstError) {
                firstError.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 200);
    }
}" 
x-on:validation-failed.window="scrollToError()"
x-on:keydown.escape.window="$wire.closeQuickForm()" 
x-cloak 
class="fixed inset-0 z-[99999999] overflow-y-auto">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeQuickForm"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl overflow-hidden bg-white">
            <div class="bg-gradient-to-br from-[#E5F9FF] via-white to-[#F0FAFF] p-6 max-h-[85vh] overflow-y-auto">

                {{-- Modal Header --}}
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-2xl font-bold text-[#003B5C]">
                            @if($viewMode) View Indicator @elseif($editingQuickFormId) Edit Indicator @else Create Indicator @endif
                        </h3>
                    </div>
                    <button wire:click="closeQuickForm" class="text-gray-500 hover:text-gray-800">✕</button>
                </div>

                {{-- Chart (If View Mode) --}}
                @if(($viewMode || $isUpdateProgress) && !empty($chartData))
                    <div class="mb-6 p-4 bg-white rounded-xl border border-blue-100 shadow-sm">
                        <h4 class="text-sm font-bold text-[#003B5C] mb-2">Tracking Progress</h4>
                        <div class="flex items-end justify-between h-32 gap-1 px-2 border-b border-gray-200">
                            @php
                                $maxVal = 0;
                                foreach($chartData as $d) {
                                    $maxVal = max($maxVal, $d['target'], $d['actual']);
                                }
                                $maxVal = $maxVal > 0 ? $maxVal : 1;
                            @endphp

                            @foreach($chartData as $data)
                                @php
                                    $targetH = ($data['target'] / $maxVal) * 100;
                                    $actualH = ($data['actual'] / $maxVal) * 100;

                                    $barColor = 'bg-[#02aeef]';
                                    if ($data['actual'] == 0 && $data['target'] > 0) {
                                        $barColor = 'bg-red-100';
                                    } elseif ($data['actual'] >= $data['target'] && $data['target'] > 0) {
                                        $barColor = 'bg-green-500';
                                    } elseif ($data['actual'] > 0) {
                                        $barColor = 'bg-yellow-500';
                                    }
                                @endphp

                                <div class="flex flex-col items-center justify-end h-full w-full group relative">
                                    <div class="w-full flex items-end justify-center gap-0.5 h-full">
                                        @if($data['is_baseline'])
                                            <div style="height: {{ $actualH }}%" class="w-3 bg-gray-400"></div>
                                        @else
                                            <div style="height: {{ $targetH }}%" class="w-2 bg-gray-200"></div>
                                            <div style="height: {{ $actualH }}%" class="w-2 {{ $barColor }}"></div>
                                        @endif
                                    </div>
                                    <span class="text-[9px] text-gray-500 mt-1">{{ $data['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex gap-4 justify-center mt-4 text-[10px] text-gray-500">
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-gray-400"></div> Baseline</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-gray-200"></div> Target</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-green-500"></div> Met</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-yellow-500"></div> In Progress</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-red-100 border border-red-200"></div> Pending</div>
                        </div>
                    </div>
                @endif

                {{-- THE FORM --}}
                <form wire:submit.prevent="saveQuickForm" class="flex flex-col h-full bg-gray-50">

                    {{-- SCROLLABLE CONTENT AREA --}}
                    <div class="flex-1 overflow-y-auto">

                        {{-- Progress Indicator --}}
                        <div class="bg-white border-b border-gray-200 px-6 py-4">
                            <div class="flex items-center justify-between max-w-2xl mx-auto">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-[#003B5C] text-white flex items-center justify-center font-bold text-sm">1</div>
                                    <span class="text-sm font-semibold text-gray-700">Basic Info</span>
                                </div>
                                <div class="flex-1 h-1 bg-gray-200 mx-3"></div>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-[#00AEEF] text-white flex items-center justify-center font-bold text-sm">2</div>
                                    <span class="text-sm font-semibold text-gray-700">Targets</span>
                                </div>
                                <div class="flex-1 h-1 bg-gray-200 mx-3"></div>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-sm">3</div>
                                    <span class="text-sm font-semibold text-gray-700">Notes</span>
                                </div>
                            </div>
                        </div>

                        <div class="max-w-4xl mx-auto p-6 space-y-6">

                            {{-- SECTION 1: What are you reporting? --}}
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-bold text-[#003B5C] mb-5 pb-3 border-b border-gray-200">
                                    What are you reporting?
                                </h3>

                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Category <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model.live="quickForm.category"
                                                class="w-full h-11 rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}>
                                            <option value="">Choose a category...</option>
                                            @foreach($indicatorCategories as $category)
                                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('quickForm.category') <span class="text-xs text-red-600 font-medium mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Dynamic Fields --}}
                                    @if(!empty($dynamicFields))
                                        @foreach($dynamicFields as $field)
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    {{ $field->field_label }} @if($field->is_required) <span class="text-red-500">*</span> @endif
                                                </label>

                                                @if($field->field_name === 'pillar')
                                                    {{-- Pillar Dropdown --}}
                                                    <select wire:model="quickForm.pillar_id"
                                                        class="w-full h-11 rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                        {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}>
                                                        <option value="">Select Pillar...</option>
                                                        @foreach($pillars as $pillar)
                                                            <option value="{{ $pillar->id }}">{{ $pillar->value }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field->field_name === 'outcome')
                                                    {{-- Outcome Dropdown --}}
                                                    <select wire:model="quickForm.outcome_id"
                                                        class="w-full h-11 rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                        {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}>
                                                        <option value="">Select Outcome...</option>
                                                        @foreach($outcomes as $outcome)
                                                            <option value="{{ $outcome->id }}">{{ $outcome->value }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field->field_name === 'strategy')
                                                    {{-- Strategy Dropdown --}}
                                                    <select wire:model="quickForm.strategy_id"
                                                        class="w-full h-11 rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                        {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}>
                                                        <option value="">Select Strategy...</option>
                                                        @foreach($strategies as $strategy)
                                                            <option value="{{ $strategy->id }}">{{ $strategy->value }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field->field_type === 'textarea')
                                                    <textarea wire:model="dynamicValues.{{ $field->field_name }}" rows="3"
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                        {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}></textarea>
                                                @elseif($field->field_type === 'select')
                                                    <select wire:model="dynamicValues.{{ $field->field_name }}"
                                                        class="w-full h-11 rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                        {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}>
                                                        <option value="">Select...</option>
                                                        @if(!empty($field->options))
                                                            @foreach($field->options as $opt)
                                                                <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                @else
                                                    <input type="{{ $field->field_type }}" wire:model="dynamicValues.{{ $field->field_name }}"
                                                        class="w-full h-11 rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                        {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} />
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Indicator Name --}}
                                    @php $hasCustomIndicator = collect($dynamicFields)->contains('db_column', 'indicator'); @endphp
                                    @if(!$hasCustomIndicator)
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                What indicator are you tracking? <span class="text-red-500">*</span>
                                            </label>
                                            <textarea wire:model.defer="quickForm.indicator" rows="2"
                                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                    {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} placeholder="e.g. Number of trainings completed"></textarea>
                                            @error('quickForm.indicator') <span class="text-xs text-red-600 font-medium mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Operational Definition <span class="text-gray-500 text-xs font-normal">(Optional)</span>
                                        </label>
                                        <textarea wire:model.defer="quickForm.operational_definition" rows="2"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} placeholder="Briefly explain the calculation or definition"></textarea>
                                    </div>

                                    {{-- Agency Info - Simplified --}}
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm">
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                    <span class="font-medium">Reporting Agency:</span>
                                                </div>
                                                <div class="font-bold text-[#003B5C]">
                                                    {{ $quickForm['reporting_agency'] ?? '—' }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                    <span class="font-medium">Responsible Agency:</span>
                                                </div>
                                                <div class="font-bold text-[#003B5C]">
                                                    {{ $quickForm['responsible_agency'] ?? '—' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- SECTION 2: Set your targets --}}
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-bold text-[#00AEEF] mb-5 pb-3 border-b border-gray-200">
                                    Set your targets
                                </h3>

                                <div class="space-y-5">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Start Year <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" min="1900" max="2100" wire:model.live.debounce.500ms="quickForm.year_start"
                                                class="w-full h-11 text-center font-semibold border-gray-300 rounded-lg focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} placeholder="2024">
                                            @error('quickForm.year_start') <span class="text-xs text-red-600 font-medium mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                End Year
                                            </label>
                                            <input type="number" min="1900" max="2100" wire:model.live.debounce.500ms="quickForm.year_end"
                                                class="w-full h-11 text-center font-semibold border-gray-300 rounded-lg focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} placeholder="2028">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Starting Value
                                            </label>
                                            <input type="text" wire:model.defer="quickForm.baseline"
                                                class="w-full h-11 text-center font-semibold border-gray-300 rounded-lg focus:border-[#00AEEF] focus:ring-2 focus:ring-[#00AEEF]/20"
                                                {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} placeholder="0">
                                            @error('quickForm.baseline') <span class="text-xs text-red-600 font-medium mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    @if(!empty($breakdown))
                                        <div>
                                            <p class="text-sm text-gray-600 mb-3">Enter your target and actual values for each year:</p>
                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                <table class="min-w-full text-sm">
                                                    <thead class="bg-gray-100">
                                                        <tr>
                                                            <th class="py-3 px-4 text-left font-semibold text-gray-700">Year</th>
                                                            <th class="py-3 px-4 text-center font-semibold text-gray-700">Target</th>
                                                            <th class="py-3 px-4 text-center font-semibold text-gray-700">Actual</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @foreach($breakdown as $index => $row)
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="py-2 px-4 font-medium text-gray-700">
                                                                    {{ $row['year'] }}
                                                                </td>
                                                                <td class="py-2 px-4">
                                                                    <input type="number" min="0" step="any" wire:model="breakdown.{{ $index }}.target"
                                                                        class="w-full text-center border border-gray-200 rounded px-2 py-1.5 focus:border-[#00AEEF] focus:ring-1 focus:ring-[#00AEEF]/20"
                                                                        {{ ($viewMode && !$isUpdateProgress && !$adminBypassMode) ? 'disabled' : '' }} placeholder="0">
                                                                </td>
                                                                <td class="py-2 px-4">
                                                                    <input type="number" min="0" step="any" wire:model="breakdown.{{ $index }}.actual"
                                                                        class="w-full text-center border border-gray-200 rounded px-2 py-1.5 font-medium text-[#003B5C] focus:border-[#00AEEF] focus:ring-1 focus:ring-[#00AEEF]/20"
                                                                        {{ ($viewMode && !$isUpdateProgress && !$adminBypassMode) ? 'disabled' : '' }} placeholder="0">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="bg-blue-50 border-t-2 border-gray-200">
                                                        <tr>
                                                            <td class="py-3 px-4 font-semibold text-gray-700">Total</td>
                                                            <td class="py-3 px-4 text-center font-bold text-gray-800">
                                                                @php
                                                                    $totalTarget = collect($breakdown)->sum(fn($row) => is_numeric($row['target'] ?? 0) ? (float)($row['target'] ?? 0) : 0);
                                                                @endphp
                                                                {{ number_format($totalTarget, 0, '', '') }}
                                                                @error('quickForm.target') <div class="text-[10px] text-red-600 font-normal mt-1">{{ $message }}</div> @enderror
                                                            </td>
                                                            <td class="py-3 px-4 text-center font-bold text-[#003B5C]">
                                                                @php
                                                                    $totalActual = collect($breakdown)->sum(fn($row) => is_numeric($row['actual'] ?? 0) ? (float)($row['actual'] ?? 0) : 0);
                                                                @endphp
                                                                {{ number_format($totalActual, 0, '', '') }}
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-8 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg">
                                            <p class="text-sm text-gray-500">Enter a start year above to begin</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- SECTION 3: Additional notes --}}
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-bold text-purple-600 mb-5 pb-3 border-b border-gray-200">
                                    Additional information <span class="text-sm font-normal text-gray-500">(Optional)</span>
                                </h3>

                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Mode of Verification
                                        </label>
                                        <textarea wire:model.defer="quickForm.mov" rows="2"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                                                {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }} placeholder="e.g. Reports, certificates, documents"></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Assumptions
                                            </label>
                                            <textarea wire:model.defer="quickForm.assumptions" rows="2"
                                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                                                    {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Remarks
                                            </label>
                                            <textarea wire:model.defer="quickForm.remarks" rows="2"
                                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                                                    {{ ($viewMode && !$adminBypassMode) ? 'disabled' : '' }}></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Lifecycle History Timeline (View Mode Only) --}}
                    @if($viewMode && isset($indicatorHistory) && count($indicatorHistory) > 0)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Lifecycle History</h4>
                        <div class="relative">
                            {{-- Vertical Line --}}
                            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                            @foreach($indicatorHistory as $history)
                                <div class="relative pl-10 pb-6">
                                    {{-- Timeline Dot --}}
                                    <div class="absolute left-2.5 w-3 h-3 rounded-full
                                        {{ $history->action === 'reject' ? 'bg-red-500' :
                                           ($history->action === 'approve' ? 'bg-green-500' :
                                           ($history->action === 'update_proof' ? 'bg-emerald-500' : 'bg-blue-500')) }}">
                                    </div>

                                    {{-- Content --}}
                                    <div class="bg-gray-50 rounded-lg p-3 @if($history->action === 'reject') border-l-4 border-red-500 @endif">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <span class="font-semibold text-gray-900">
                                                    {{ $history->action_label }}
                                                </span>
                                                @if($history->actor_user_id && $history->actor && $history->actor->exists)
                                                    <span class="text-sm text-gray-500">by {{ $history->actor->name }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs text-gray-400">{{ $history->created_at->format('M j, Y g:i A') }}</span>
                                        </div>

                                        {{-- Rejection Note Highlight --}}
                                        @if($history->rejection_note)
                                            <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                                                <strong>Reason:</strong> {{ $history->rejection_note }}
                                            </div>
                                        @endif

                                        {{-- Status Change Detail --}}
                                        @if($history->new_values && isset($history->new_values['status']))
                                            <div class="mt-1 text-xs text-gray-500">
                                                Status: {{ $history->old_values['status'] ?? 'N/A' }} →
                                                <span class="font-medium text-gray-700">{{ $history->new_values['status'] }}</span>
                                            </div>
                                        @endif

                                        {{-- Proof Upload Details --}}
                                        @if($history->action === 'update_proof' && $history->new_values)
                                            <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm">
                                                <div class="flex items-center gap-2 text-green-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span class="font-medium">Proof uploaded for Year {{ $history->new_values['year'] ?? 'N/A' }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-green-700">
                                                    Actual Value: <strong>{{ number_format($history->new_values['actual_value'] ?? 0) }}</strong>
                                                    @if(isset($history->new_values['mfo_reference']))
                                                        • MFO: <span class="font-mono">{{ $history->new_values['mfo_reference'] }}</span>
                                                    @endif
                                                    @if(isset($history->new_values['proof_file']))
                                                        • File: {{ $history->new_values['proof_file'] }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Proofs Section (View Mode Only) --}}
                    @if($viewMode && isset($indicatorProofs) && count($indicatorProofs) > 0)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center gap-2 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h4 class="text-lg font-bold text-gray-900">Proof Documents ({{ count($indicatorProofs) }})</h4>
                        </div>

                        <div class="space-y-2">
                            @foreach($indicatorProofs as $proof)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-gray-200 hover:border-green-300 hover:bg-green-50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 p-2 rounded bg-green-100 text-green-600">
                                            @if(str_ends_with(strtolower($proof->file_name), '.pdf') || str_ends_with(strtolower($proof->file_name), '.doc') || str_ends_with(strtolower($proof->file_name), '.docx'))
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                                    <path d="M14 2v6h6M16 13H8"/>
                                                    <path d="M16 17H8"/>
                                                    <path d="M10 9H8"/>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                                    <path d="M21 15l-5-5L5 15"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $proof->file_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $proof->human_file_size }} •
                                                Uploaded by {{ $proof->uploader->name ?? 'Unknown' }} •
                                                {{ $proof->created_at->format('M j, Y g:i A') }}
                                                @if($proof->year)
                                                    • Year: {{ $proof->year }}
                                                @endif
                                                @if($proof->mfo_reference)
                                                    • MFO: {{ $proof->mfo_reference }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ $proof->url }}" target="_blank"
                                       class="px-3 py-1.5 text-sm rounded-lg border border-green-300 text-green-700 hover:bg-green-50 transition">
                                        View/Download
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- FIXED FOOTER ACTIONS --}}
                    <div class="border-t border-gray-200 bg-white px-6 py-4">
                        <div class="max-w-4xl mx-auto flex justify-between items-center">
                            <button type="button" wire:click="closeQuickForm"
                                    class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 transition">
                                {{ $viewMode ? 'Close' : 'Cancel' }}
                            </button>

                            @if(!$viewMode)
                                <button type="submit"
                                        class="px-8 py-2.5 rounded-lg bg-[#02aeef] text-white text-sm font-semibold hover:bg-[#0299d5] shadow-md transition flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ $editingQuickFormId ? 'Update' : 'Save' }}
                                </button>
                            @endif

                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endif
