@props([
    'show' => false,
    'rejectionReason' => '',
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
            @click.away="open = false; $wire.closeRejectionModal()">

           <h3 class="text-xl font-bold text-red-600 mb-2">Return to Sender</h3>
           <p class="text-sm text-gray-600 mb-4">
               Please provide a detailed reason for returning this indicator. This feedback will be sent to the submitter.
           </p>

           <textarea
               wire:model.live="rejectionReason"
               rows="4"
               placeholder="Explain why this indicator is being returned..."
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
           ></textarea>

           @error('rejectionReason')
               <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
           @enderror

           <div class="flex justify-end gap-3 mt-6">
               <button wire:click="closeRejectionModal()"
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                   Cancel
               </button>
               <button wire:click="submitRejection()"
                       wire:loading.attr="disabled"
                       class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                   Return to Sender
               </button>
           </div>
       </div>
   </div>
</div>
@endif
