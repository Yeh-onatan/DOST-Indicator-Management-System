<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
     x-data="{ show: @entangle('showForm') }"
     x-show="show"
     x-transition.opacity
     style="display: none;">
    
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
        
        {{-- HEADER --}}
        <div class="flex items-center justify-between px-6 py-5 border-b bg-white sticky top-0 z-10">
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 flex items-center gap-3">
                    {{ $form['id'] ? 'Edit Indicator' : 'New Indicator' }}
                    
                    @if($isLocked)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                            <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Plan Locked
                        </span>
                    @else
                         <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                            {{ $form['status'] ?? 'Draft' }}
                        </span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $is_mandatory ? 'Mandatory Indicator' : 'Standard Indicator' }} 
                    @if($isLocked)
                        • <span class="text-purple-600">Only progress updates are allowed.</span>
                    @endif
                </p>
            </div>
            <button wire:click="closeForm" class="text-gray-400 hover:text-gray-600 transition p-2 rounded-full hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- SCROLLABLE CONTENT --}}
        <div class="flex-1 overflow-y-auto p-8 bg-gray-50/50">
            
            {{-- Rejection Notice --}}
            @if($form['rejection_note'] && !$isLocked)
                <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg flex gap-4 items-start shadow-sm">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-red-800">Returned for corrections</h3>
                        <p class="mt-1 text-sm text-red-700 leading-relaxed">{{ $form['rejection_note'] }}</p>
                    </div>
                </div>
            @endif

            {{-- ALL FORM CONTENT (Single View) --}}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="category" value="Category" />
                            <select wire:model.live="selectedCategory" id="category" 
                                    @disabled($isLocked)
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 transition">
                                <option value="">Select Category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedCategory')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <x-input-label for="admin_name" value="Administration" />
                            <x-text-input wire:model="form.admin_name" id="admin_name" class="mt-1 block w-full rounded-lg" :disabled="$isLocked" />
                        </div>
                    </div>
                    
                    <div>
                        <x-input-label value="Target Period (Start - End)" />
                        <div class="flex gap-3 mt-1">
                            <div class="w-1/2">
                                <x-text-input wire:model.live="form.year_start" type="number" min="1900" max="2100" placeholder="Start Year" class="w-full rounded-lg" :disabled="$isLocked" />
                            </div>
                            <span class="self-center text-gray-400 font-bold">-</span>
                            <div class="w-1/2">
                                <x-text-input wire:model.live="form.year_end" type="number" min="1900" max="2100" placeholder="End Year" class="w-full rounded-lg" :disabled="$isLocked" />
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Defines the coverage of this indicator.</p>
                    </div>
                </div>

                <div>
                    <x-input-label for="indicator" value="Indicator Description" />
                    <textarea wire:model="form.indicator_description" id="indicator" rows="4" 
                              @disabled($isLocked)
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 transition"
                              placeholder="Enter the full description of the indicator..."></textarea>
                    @error('form.indicator_description')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if(count($categoryFields) > 0)
                    <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Additional Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($categoryFields as $field)
                                <div class="col-span-{{ $field['field_type'] === 'textarea' ? '2' : '1' }}">
                                    <x-input-label :for="$field['field_name']" :value="$field['label']" />

                                    @if($field['field_type'] === 'select')
                                        <select wire:model="dynamicFields.{{ $field['field_name'] }}"
                                                @disabled($isLocked)
                                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 transition">
                                            <option value="">Select...</option>
                                            @foreach($field['options'] ?? [] as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($field['field_type'] === 'textarea')
                                        <textarea wire:model="dynamicFields.{{ $field['field_name'] }}" rows="3"
                                                  @disabled($isLocked)
                                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 transition"></textarea>
                                    @else
                                        <x-text-input wire:model="dynamicFields.{{ $field['field_name'] }}" :type="$field['field_type']" class="mt-1 block w-full rounded-lg" :disabled="$isLocked" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Strategic Plan Values --}}
                <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Strategic Plan Values</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-input-label for="pillar_id" value="Pillar" />
                            <select wire:model="form.pillar_id" id="pillar_id"
                                    @disabled($isLocked)
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 transition">
                                <option value="">Select Pillar...</option>
                                @foreach($pillars as $id => $value)
                                    <option value="{{ $id }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select the strategic plan pillar (numeric value)</p>
                        </div>
                        <div>
                            <x-input-label for="outcome_id" value="Outcome" />
                            <select wire:model="form.outcome_id" id="outcome_id"
                                    @disabled($isLocked)
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 transition">
                                <option value="">Select Outcome...</option>
                                @foreach($outcomes as $id => $value)
                                    <option value="{{ $id }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select the strategic plan outcome (numeric value)</p>
                        </div>
                        <div>
                            <x-input-label for="strategy_id" value="Strategy" />
                            <select wire:model="form.strategy_id" id="strategy_id"
                                    @disabled($isLocked)
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 transition">
                                <option value="">Select Strategy...</option>
                                @foreach($strategies as $id => $value)
                                    <option value="{{ $id }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select the strategic plan strategy (numeric value)</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <x-input-label for="baseline" value="Baseline Value" class="mb-2" />
                        <x-text-input wire:model="form.baseline" id="baseline" class="block w-full text-lg font-mono" :disabled="$isLocked" />
                        @error('form.baseline')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="p-5 bg-blue-50 rounded-xl border border-blue-100 shadow-sm">
                        <x-input-label for="target" value="Total Target Value" class="mb-2 text-blue-800" />
                        <x-text-input wire:model="form.target_value" id="target" class="block w-full text-lg font-mono text-blue-700 font-bold border-blue-300 focus:ring-blue-500" :disabled="$isLocked" />
                        @error('form.target_value')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Annual Breakdown
                    </h3>
                    <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm bg-white">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Target</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider bg-green-50 text-green-700">Actual (Accomplishment)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($breakdown as $index => $row)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 bg-gray-50/50">
                                            {{ $row['year'] }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <input type="number" min="0" step="any"
                                                   wire:model="breakdown.{{ $index }}.target"
                                                   @disabled($isLocked)
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-400 transition"
                                                   placeholder="-">
                                        </td>
                                        <td class="px-6 py-3 bg-green-50/30">
                                            {{-- ACTUALS ARE ALWAYS EDITABLE --}}
                                            <input type="number" min="0" step="any"
                                                   wire:model="breakdown.{{ $index }}.actual"
                                                   class="block w-full rounded-md border-green-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm bg-white text-green-700 font-medium transition"
                                                   placeholder="Result">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tracking Progress Chart --}}
                @if($this->chartData && !empty($this->chartData['data']))
                    <div class="mb-6 p-5 bg-white rounded-xl border border-blue-100 shadow-sm">
                        <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                            Tracking Progress Visualization
                        </h4>
                        <div class="flex items-end justify-between h-40 gap-2 px-4 pb-6 border-b border-gray-200 bg-gray-50/50 rounded-lg">
                            @php
                                $maxVal = $this->chartData['max'] ?? 1;
                            @endphp
                            @foreach($this->chartData['data'] as $data)
                                @php
                                    $targetH = ($data['target'] / $maxVal) * 100;
                                    $actualH = ($data['actual'] / $maxVal) * 100;
                                    $barColor = 'bg-blue-500';
                                    if ($data['actual'] == 0 && $data['target'] > 0) {
                                        $barColor = 'bg-red-400'; 
                                    } elseif ($data['actual'] >= $data['target'] && $data['target'] > 0) {
                                        $barColor = 'bg-green-500'; 
                                    } elseif ($data['actual'] > 0) {
                                        $barColor = 'bg-yellow-500'; 
                                    }
                                @endphp
                                <div class="flex flex-col items-center justify-end h-full w-full group relative">
                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10">
                                        Target: {{ number_format($data['target'], 2) }} | Actual: {{ number_format($data['actual'], 2) }}
                                    </div>
                                    <div class="w-full flex items-end justify-center gap-1 h-full">
                                        <div style="height: {{ max($targetH, 5) }}%" class="w-3 bg-gray-200 rounded-t-sm min-h-[4px] transition-all duration-300" title="Target: {{ number_format($data['target'], 2) }}"></div>
                                        <div style="height: {{ max($actualH, 5) }}%" class="w-3 {{ $barColor }} rounded-t-sm min-h-[4px] transition-all duration-300" title="Actual: {{ number_format($data['actual'], 2) }}"></div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 mt-2 font-medium">{{ $data['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-center gap-6 mt-4 text-xs text-gray-600">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-gray-200 rounded-sm"></div>
                                <span>Target</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-blue-500 rounded-sm"></div>
                                <span>On Track</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded-sm"></div>
                                <span>Met/Exceeded</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded-sm"></div>
                                <span>In Progress</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-red-400 rounded-sm"></div>
                                <span>No Progress</span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <x-input-label for="mov" value="Mode of Verification (MOV)" />
                        <textarea wire:model="form.mov" id="mov" rows="3" 
                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition" 
                                  placeholder="Paste links to documents, files, or evidence here..."></textarea>
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            This field remains editable for updates.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="assumptions" value="Assumptions & Risks" />
                            <textarea wire:model="form.assumptions_risk" id="assumptions" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition"></textarea>
                        </div>
                        <div>
                            <x-input-label for="remarks" value="General Remarks" />
                            <textarea wire:model="form.remarks" id="remarks" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition"></textarea>
                        </div>
                    </div>
                </div>
        </div>

        {{-- Proof Upload (required when editing approved indicator) --}}
        @if($form && isset($form->status) && $form->status === \App\Models\Indicator::STATUS_APPROVED)
            <div class="p-6 border-t bg-orange-50">
                <div class="flex items-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-orange-800">Proof Required for Approved Indicators</h3>
                </div>
                <p class="text-xs text-orange-900 mb-3">You are editing an APPROVED indicator. Please upload a document (PDF, DOC, DOCX, JPG, PNG) as proof of the update.</p>
                <input type="file"
                       wire:model.live="form.proof_file"
                       class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                @error('form.proof_file')
                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
                @if(isset($form->proof_file) && $form->proof_file)
                    <p class="text-xs text-gray-600 mt-2">Selected: {{ $form->proof_file->getClientOriginalName() }}</p>
                @endif
            </div>
        @endif

        {{-- FOOTER ACTIONS --}}
        <div class="p-6 border-t bg-white flex justify-end gap-3 sticky bottom-0 z-10">
            <button wire:click="closeForm" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition shadow-sm">
                Cancel
            </button>
            <button wire:click="save" class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition shadow-md flex items-center gap-2">
                @if($isLocked)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Update Progress
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Save & Submit
                @endif
            </button>
        </div>
    </div>
</div>