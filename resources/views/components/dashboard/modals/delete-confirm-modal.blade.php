@props([
    'show' => false,
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
            @click.away="open = false; $wire.closeDeleteConfirmModal()">

           {{-- Warning Icon --}}
           <div class="flex justify-center mb-4">
               <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                   <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                   </svg>
               </div>
           </div>

           <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Delete Indicator</h3>
           <p class="text-sm text-gray-600 mb-6 text-center">
               Are you sure you want to delete this indicator? This action cannot be undone.
           </p>

           <div class="flex justify-end gap-3">
               <button wire:click="closeDeleteConfirmModal()"
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                   Cancel
               </button>
               <button wire:click="executeDelete()"
                       wire:loading.attr="disabled"
                       class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                   Delete
               </button>
           </div>
       </div>
   </div>
</div>
@endif
