<div
  class="p-6 shadow-xl sm:rounded-lg w-full max-w-full text-sm leading-snug"
  style="background: linear-gradient(135deg, #E5F9FF 0%, #D7F4FF 50%, #FFFFFF 100%);"
  x-data
  x-init="window.addEventListener('objective-form-scroll-top', () => window.scrollTo({ top: 0, behavior: 'smooth' }))"
  @keydown.escape.window="$dispatch('close-objective-modal')"
  x-on:edit-objective.window="$wire.loadObjective($event.detail.id)"
  x-on:prefill-indicator.window="$wire.prefillIndicator($event.detail.code ?? null, $event.detail.name ?? null, $event.detail.description ?? null)"
  x-on:prefill-chapter.window="$wire.prefillChapter($event.detail.id ?? null, $event.detail.outcome ?? null, $event.detail.category ?? null)"
>
  @php
    $categoryLabel = match(strtolower($current_category ?? '')) {
      'pdp' => 'PDP Indicator',
      'strategic_plan' => 'Strategic Plan Indicator',
      'prexc' => 'PREXC Indicator',
      'agency_specifics' => 'Agency-Specific Indicator',
      default => 'Indicator',
    };
  @endphp

  <h3 class="text-xl font-bold mb-2" style="color: #003B5C;">
    @if($viewOnly)
      View {{ $categoryLabel }}
    @elseif($editingId)
      Edit {{ $categoryLabel }}
    @else
      Add New {{ $categoryLabel }}
    @endif
  </h3>

  <p class="mb-4" style="color: #525252;">
    @if($viewOnly)
      Review the details of this {{ $categoryLabel }}.
    @else
      Input details for this {{ $categoryLabel }}.
    @endif
  </p>

  {{-- Loading overlay removed per request --}}

  {{-- Progress --}}
  <div class="flex items-center gap-2 mb-4">
    @for ($i = 1; $i <= $maxSteps; $i++)
      <div class="flex-1 h-2 rounded-full" style="background: {{ $currentStep >= $i ? 'linear-gradient(90deg, #003B5C, #00AEEF)' : '#E0E0E0' }};"></div>
    @endfor
  </div>
  @if($lastSavedAt)
    <div class="text-xs text-emerald-600 dark:text-emerald-300 mb-2">Saved {{ $lastSavedAt }}</div>
  @endif
  @if($errors->any())
    <div class="mb-3 text-xs text-red-500 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded px-3 py-2">
      Please fix the highlighted fields below before continuing.
    </div>
  @endif

  @php
    $restrict = $editingId && $status === 'REJECTED' && is_array($corrections_required) && count($corrections_required) > 0;
  @endphp

  {{-- =============================== --}}
  {{-- =========== FORM ============= --}}
  {{-- =============================== --}}

  <form wire:submit.prevent="submitObjective" class="space-y-4">

    {{-- STEP 1 --}}
    @if ($currentStep === 1)
      <div class="space-y-6">

        <div>
          <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Indicator *</label>
          @if($lock_indicator)
            <div
              class="w-full rounded px-3 py-2 text-sm"
              style="border: 2px solid #00AEEF; background: #F7FDFF; color: #000000;"
            >
              {{ $indicator ?: 'No indicator selected' }}
            </div>
          @else
            <input
              type="text"
              wire:model.defer="indicator"
              class="w-full rounded px-3 py-2"
              style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
              placeholder="e.g., Gross Expenditure on R&D"
              @if($viewOnly) disabled readonly @endif
            >
          @endif
          @error('indicator') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Operational Definition</label>
          <textarea
            wire:model.defer="description"
            rows="4"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
            @if($viewOnly) disabled readonly @endif
          ></textarea>
          @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Category-specific context shown in view/edit --}}
        @if($current_category === 'pdp')
          <div>
            <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">PDP Outcome</label>
            <input
              type="text"
              wire:model.defer="objective_result"
              class="w-full rounded px-3 py-2"
              style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
              @if($viewOnly) disabled readonly @endif
            >
          </div>
        @elseif($current_category === 'strategic_plan')
          <div>
            <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Plan Details (Pillar / Strategy)</label>
            <input
              type="text"
              wire:model.defer="objective_result"
              class="w-full rounded px-3 py-2"
              style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
              @if($viewOnly) disabled readonly @endif
            >
          </div>
        @elseif($current_category === 'prexc')
          <div>
            <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Monitoring Mechanism</label>
            <textarea
              wire:model.defer="pc_secretariat_remarks"
              rows="3"
              class="w-full rounded px-3 py-2"
              style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
              @if($viewOnly) disabled readonly @endif
            ></textarea>
          </div>
        @endif

        <div>
          <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Assigned Office</label>
          <select
            wire:model="office_id"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
            @if($viewOnly) disabled @endif
          >
            <option value="">None / N/A</option>
            @foreach($offices as $office)
              <option value="{{ $office->id }}">{{ $office->name }}</option>
            @endforeach
          </select>
          <p class="text-xs mt-1" style="color: #525252;">Region is determined automatically from the office.</p>
          @error('office_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">DOST Agency / Office</label>
          <select
            wire:model.defer="dost_agency"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
            @if($viewOnly) disabled @endif
          >
            <option value="">None / N/A</option>
            @foreach($agencies as $agency)
              @php
                $label = $agency->acronym ? $agency->acronym : $agency->name;
              @endphp
              <option value="{{ $label }}">{{ $label }}</option>
            @endforeach
          </select>
          @error('dost_agency') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

      </div>
    @endif


    {{-- STEP 2 --}}
    @if ($currentStep === 2)

      @php
        // FIXED: safe, sorted rows + delta calculation
        $sortedRows = collect($annual_plan_targets_series ?? [])
          ->filter(fn($r) => isset($r['year']) || isset($r['value']))
          ->sortBy('year')
          ->values();
        $prevVal = null;
        $deltas = [];
        $hasMet = collect($annual_plan_targets_series ?? [])->contains(fn($r) => !empty($r['met']));
      @endphp

      <div class="space-y-4">

      <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Start Year *</label>
            <input 
              type="number" 
              wire:model.defer="target_period_start" 
              class="w-full rounded px-3 py-2" 
              style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;" 
              placeholder="e.g. 2023"
              min="1900" 
              max="2100"
              @if($viewOnly) disabled readonly @endif
            >
            @error('target_period_start') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">End Year</label>
            <input 
              type="number" 
              wire:model.defer="target_period_end" 
              class="w-full rounded px-3 py-2" 
              style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;" 
              placeholder="e.g. 2028"
              min="1900" 
              max="2100"
              @if($viewOnly) disabled readonly @endif
            >
            @error('target_period_end') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Baseline *</label>
          <input type="text" wire:model.defer="baseline" class="w-full rounded px-3 py-2" style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;" @if($viewOnly) disabled readonly @endif>
          @error('baseline') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- FIXED: target value is required by validation; expose it so Next works --}}
        <div>
          <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Target Value *</label>
          <input type="number" min="0" step="0.01" wire:model.defer="target_value" class="w-full rounded px-3 py-2" style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;" @if($viewOnly) disabled readonly @endif>
          @error('target_value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        @if($hasMet)
          <div>
            <label class="block text-sm font-semibold mb-1" style="color: #003B5C;">Accomplishments</label>
            <input type="text" wire:model.defer="accomplishments" class="w-full rounded px-3 py-2" style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;" @if($viewOnly) disabled readonly @endif>
          </div>
        @endif

        <div class="flex flex-wrap gap-2 items-end">
          <button type="button" wire:click="addPlanRow"
            class="px-3 py-1 rounded text-sm font-semibold"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #003B5C;">Add Year</button>
          {{-- Generator for quick yearly targets --}}
          <div class="flex flex-wrap gap-2 text-xs">
            <input type="number" min="1900" max="2100" wire:model.live="gen_start_year" class="w-20 rounded px-2 py-1" style="border: 2px solid #00AEEF; background: #FFFFFF;" placeholder="Start">
            <input type="number" min="1900" max="2100" wire:model.defer="gen_end_year" class="w-20 rounded px-2 py-1" style="border: 2px solid #00AEEF; background: #FFFFFF;" placeholder="End">
            <input type="number" min="0" step="0.01" wire:model.defer="gen_baseline_value" class="w-24 rounded px-2 py-1" style="border: 2px solid #00AEEF; background: #FFFFFF;" placeholder="Value">
            <input type="number" step="0.01" wire:model.defer="gen_increment" class="w-20 rounded px-2 py-1" style="border: 2px solid #00AEEF; background: #FFFFFF;" placeholder="+/yr">
            <label class="flex items-center gap-1">
              <input type="checkbox" wire:model.defer="gen_overwrite"> Overwrite
            </label>
            <button type="button" wire:click="generateTargets" class="px-3 py-1 rounded font-semibold" style="background: linear-gradient(90deg, #003B5C, #00527A, #00AEEF); color: #FFFFFF;">Generate</button>
          </div>
        </div>
        @error('gen_start_year') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @error('gen_end_year') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @error('gen_baseline_value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @error('gen_increment') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @error('annual_plan_targets_series.*.year') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @error('annual_plan_targets_series.*.value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

        <div class="space-y-2 mt-3">

          @forelse ($sortedRows as $i => $row)
            @php
              $delta = ($prevVal !== null && is_numeric($row['value'] ?? null))
                ? (float)$row['value'] - (float)$prevVal
                : null;
              $deltas[] = $delta;
            @endphp

            <div class="flex gap-2 items-center">

              <input type="number" min="1900" max="2100" wire:model.defer="annual_plan_targets_series.{{ $i }}.year"
                class="w-24 rounded px-2 py-1" style="border: 2px solid #00AEEF; background: #FFFFFF;" placeholder="Year">

              <input type="number" min="0" step="0.01"
                wire:model.defer="annual_plan_targets_series.{{ $i }}.value"
                class="flex-1 rounded px-2 py-1" style="border: 2px solid #00AEEF; background: #FFFFFF;" placeholder="Target value">

              <div class="w-28 text-[10px] text-[var(--text-muted)]">
                @if($delta !== null)
                  {{-- FIXED: clean delta display, safe for empty rows --}}
                  Delta {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta,2) }}
                @else
                  &nbsp;
                @endif
              </div>

              <label class="text-xs flex items-center gap-1">
                <input type="checkbox" wire:model="annual_plan_targets_series.{{ $i }}.met">
                Met
              </label>

              <button type="button"
                wire:click="removePlanRow({{ $i }})"
                class="text-red-500 text-xs">Remove</button>

            </div>

            @php
              $prevVal = is_numeric($row['value'] ?? null) ? (float)$row['value'] : $prevVal;
            @endphp

          @empty
            <p class="text-xs text-gray-500">No yearly targets yet. Click Add Year to start.</p>
          @endforelse

        </div>

      </div>
    @endif


    {{-- STEP 3 --}}
    @if ($currentStep === 3)

      <div class="space-y-4">

        <div>
          <p class="text-sm font-semibold" style="color: #003B5C;">Responsibilities & Notes</p>
          <p class="text-xs" style="color: #525252;">Fill who reports, who verifies, what proves it, and any PC Secretariat remarks.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold mb-1" style="color: #003B5C;">Means of Verification *</label>
          <input type="text"
            wire:model.defer="mov"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
            placeholder="Document or evidence to prove accomplishment">
          @error('mov') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-semibold mb-1" style="color: #003B5C;">Responsible Agency *</label>
          <select
            wire:model.defer="responsible_agency"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
          >
            <option value="N/A">None / N/A</option>
            @php
              $responsibleExistsInList = $agencies->contains(fn($agency) => ($agency->name === $responsible_agency) || ($agency->acronym === $responsible_agency));
            @endphp
            @if($responsible_agency && !$responsibleExistsInList)
              <option value="{{ $responsible_agency }}" selected>{{ $responsible_agency }}</option>
            @endif
            @foreach($agencies as $agency)
              <option value="{{ $agency->name }}">{{ $agency->acronym ?? $agency->name }}</option>
            @endforeach
          </select>
          @error('responsible_agency') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-semibold mb-1" style="color: #003B5C;">Reporting Agency *</label>
          <select
            wire:model.defer="reporting_agency"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
          >
            <option value="N/A">None / N/A</option>
            @php
              $reportingExistsInList = $agencies->contains(fn($agency) => ($agency->name === $reporting_agency) || ($agency->acronym === $reporting_agency));
            @endphp
            @if($reporting_agency && !$reportingExistsInList)
              <option value="{{ $reporting_agency }}" selected>{{ $reporting_agency }}</option>
            @endif
            @foreach($agencies as $agency)
              <option value="{{ $agency->name }}">{{ $agency->acronym ?? $agency->name }}</option>
            @endforeach
          </select>
          @error('reporting_agency') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-semibold mb-1" style="color: #003B5C;">Assumptions / Risks</label>
          <textarea
            wire:model.defer="assumptions_risk"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
            placeholder="Optional: dependencies, constraints, or risks"></textarea>
        </div>

        <div>
          <label class="block text-xs font-semibold mb-1" style="color: #003B5C;">PC Secretariat Remarks</label>
          <textarea
            wire:model.defer="pc_secretariat_remarks"
            class="w-full rounded px-3 py-2"
            style="border: 2px solid #00AEEF; background: #FFFFFF; color: #000000;"
            placeholder="Optional: notes from PC Secretariat"></textarea>
        </div>

        {{-- Proof Upload (required when editing approved indicator) --}}
        @if($editingId && $status === 'APPROVED')
          <div class="p-4 rounded-lg border-2 border-orange-400 bg-orange-50">
            <div class="flex items-center gap-2 mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              <span class="text-sm font-bold text-orange-800">Proof Required for Approved Indicators</span>
            </div>
            <p class="text-xs text-orange-900 mb-3">You are editing an APPROVED indicator. Please upload a document (PDF, DOC, DOCX, JPG, PNG) as proof of the update.</p>
            <input type="file"
                   wire:model.live="proof_file"
                   class="w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            @error('proof_file')
              <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
            @enderror
            @if($proof_file)
              <p class="text-xs text-gray-600 mt-2">Selected: {{ $proof_file->getClientOriginalName() }}</p>
            @endif
          </div>
        @endif

        {{-- Mandatory Assignment Section --}}
        <div class="mt-6 pt-4" style="border-top: 2px solid #00AEEF;">
          <div class="flex items-center gap-2 mb-4">
            <input type="checkbox" wire:model.live="is_mandatory" id="is_mandatory" class="rounded" style="border: 2px solid #00AEEF;">
            <label for="is_mandatory" class="text-sm font-semibold" style="color: #003B5C;">
              Mark as Mandatory Indicator
            </label>
          </div>

          @if($is_mandatory)
            <div class="rounded p-4 space-y-4" style="background: #F0FAFF; border: 1px solid #CCEFFF;">
              <p class="text-xs" style="color: #525252;">
                Assign this mandatory indicator to specific offices/agencies or all.
              </p>

              <div class="flex gap-2">
                <select wire:model.live="assignment_type" class="rounded px-3 py-2" style="border: 2px solid #00AEEF; background: #FFFFFF;">
                  <option value="all">All</option>
                  <option value="office">Specific Office</option>
                  <option value="agency">Specific Agency</option>
                </select>

                @if($assignment_type === 'office')
                  <select wire:model.live="selected_office_id" class="flex-1 rounded px-3 py-2" style="border: 2px solid #00AEEF; background: #FFFFFF;">
                    <option value="">Select Office</option>
                    @foreach($offices as $office)
                      <option value="{{ $office->id }}">{{ $office->name }}</option>
                    @endforeach
                  </select>
                @elseif($assignment_type === 'agency')
                  <select wire:model.live="selected_agency_id" class="flex-1 rounded px-3 py-2" style="border: 2px solid #00AEEF; background: #FFFFFF;">
                    <option value="">Select Agency</option>
                    @foreach($agencies as $agency)
                      <option value="{{ $agency->id }}">{{ $agency->acronym }}</option>
                    @endforeach
                  </select>
                @endif

                <button
                  type="button"
                  wire:click="addMandatoryAssignment"
                  class="px-4 py-2 rounded font-semibold"
                  style="background: linear-gradient(90deg, #003B5C, #00527A, #00AEEF); color: #FFFFFF;"
                >
                  Add
                </button>
              </div>

              @if(count($mandatory_assignments) > 0)
                <div class="space-y-2">
                  <p class="text-xs font-semibold text-[var(--text)]">Assigned To:</p>
                  @foreach($mandatory_assignments as $index => $assignment)
                    <div class="flex items-center justify-between bg-white dark:bg-neutral-900 border rounded px-3 py-2">
                      <span class="text-sm">
                        @if($assignment['assignment_type'] === 'all')
                          <strong>All</strong>
                        @elseif($assignment['assignment_type'] === 'office')
                          Office: <strong>{{ $assignment['office_name'] }}</strong>
                        @elseif($assignment['assignment_type'] === 'agency')
                          Agency: <strong>{{ $assignment['agency_name'] }}</strong>
                        @endif
                      </span>
                      <button
                        type="button"
                        wire:click="removeMandatoryAssignment({{ $index }})"
                        class="text-red-600 hover:text-red-800 text-xs"
                      >
                        Remove
                      </button>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          @endif
        </div>

      </div>

    @endif


    {{-- CONTROLS --}}
    <div class="flex justify-between mt-5">

      @if ($currentStep > 1)
        <button type="button" wire:click="previousStep"
          wire:loading.attr="disabled" wire:target="previousStep,nextStep,submitObjective"
          class="px-4 py-1 rounded font-semibold"
          style="border: 2px solid #00AEEF; background: #FFFFFF; color: #003B5C;">
          Back
        </button>
      @endif

      <div class="flex gap-2">

        @if ($currentStep < $maxSteps && !$viewOnly)
          <button type="button" wire:click="nextStep"
            wire:loading.attr="disabled" wire:target="nextStep,submitObjective"
            class="px-6 py-2 rounded font-semibold"
            style="background: linear-gradient(90deg, #003B5C, #00527A, #00AEEF); color: #FFFFFF;">
            Next
          </button>
        @elseif(!$viewOnly)
          <button type="submit"
            wire:loading.attr="disabled" wire:target="submitObjective,nextStep"
            class="px-6 py-2 rounded font-semibold"
            style="background: linear-gradient(90deg, #003B5C, #00527A, #00AEEF); color: #FFFFFF;">
            Save
          </button>

          @auth
            @if(auth()->user()->isAgency() && !$viewOnly)
              <button type="button"
                      wire:click="submitAndForwardToHO"
                      wire:loading.attr="disabled"
                      wire:target="submitObjective,submitAndForwardToHO"
                      class="px-6 py-2 rounded font-semibold ml-2 text-white"
                      style="background: linear-gradient(90deg, #00AEEF, #009ACD);">
                Save & {{ $this->agencyHasHO ? 'Submit to HO' : 'Submit to Admin' }}
              </button>
            @endif
          @endauth
        @endif

        <button type="button" @click="$dispatch('close-objective-modal')"
          class="px-4 py-1 rounded font-semibold"
          style="border: 2px solid #00AEEF; background: #FFFFFF; color: #003B5C;">
          Cancel
        </button>

        @unless($viewOnly)
          <button
            type="button"
            wire:click="resetForm"
            wire:loading.attr="disabled"
            class="px-4 py-1 rounded"
            style="border: 2px solid #E0E0E0; background: #FFFFFF; color: #757575;"
          >
            Clear Form
          </button>
        @endunless

      </div>

    </div>

  </form>
</div>
