@props([
    'show' => false,
    'approvalConfirmAction' => '',
    'approvalConfirmTitle' => '',
    'approvalConfirmMessage' => '',
    'approvalConfirmIndicator' => '',
])

@if($show)
<div x-data="{ open: true }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[99999] overflow-y-auto"
        role="dialog"
        aria-modal="true">
   <div x-show="open" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

   <div class="flex min-h-full items-center justify-center p-4">
       <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
            @click.away="open = false; $wire.closeApprovalConfirmModal()">

           {{-- Green Checkmark Icon --}}
           <div class="flex justify-center mb-4">
               <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                   <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                   </svg>
               </div>
           </div>

           <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">{{ $approvalConfirmTitle }}</h3>

           @if($approvalConfirmIndicator)
               <p class="text-xs text-gray-500 mb-1 text-center truncate px-4" title="{{ $approvalConfirmIndicator }}">
                   {{ \Illuminate\Support\Str::limit($approvalConfirmIndicator, 80) }}
               </p>
           @endif

           <p class="text-sm text-gray-600 mb-6 text-center">
               {{ $approvalConfirmMessage }}
           </p>

           <div class="flex justify-end gap-3">
               <button wire:click="closeApprovalConfirmModal()"
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                   Cancel
               </button>
               <button wire:click="executeApproval()"
                       wire:loading.attr="disabled"
                       class="px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition">
                   <span wire:loading.remove wire:target="executeApproval">Approve & Forward</span>
                   <span wire:loading wire:target="executeApproval">Processing...</span>
               </button>
           </div>
       </div>
   </div>
</div>
@endif
