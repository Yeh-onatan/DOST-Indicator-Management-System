@props([
    'show' => false,
    'adminConfirmAction' => '',
    'adminConfirmTitle' => '',
    'adminConfirmMessage' => '',
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
            @click.away="open = false; $wire.closeAdminConfirmModal()">

           {{-- Icon based on action --}}
           <div class="flex justify-center mb-4">
               @if($adminConfirmAction === 'adminDelete')
                   <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                       <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                   </div>
               @elseif($adminConfirmAction === 'adminEdit')
                   <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                       <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                   </div>
               @elseif($adminConfirmAction === 'reopen')
                   <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                       <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                   </div>
               @endif
           </div>

           <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">{{ $adminConfirmTitle }}</h3>
           <p class="text-sm text-gray-600 mb-6 text-center">
               {{ $adminConfirmMessage }}
           </p>

           <div class="flex justify-end gap-3">
               <button wire:click="closeAdminConfirmModal()"
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                   Cancel
               </button>
               <button wire:click="executeAdminAction()"
                       wire:loading.attr="disabled"
                       class="px-4 py-2 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition
                       @if($adminConfirmAction === 'adminDelete') bg-orange-600 hover:bg-orange-700
                       @elseif($adminConfirmAction === 'adminEdit') bg-purple-600 hover:bg-purple-700
                       @else bg-blue-600 hover:bg-blue-700 @endif">
                   Confirm
               </button>
           </div>
       </div>
   </div>
</div>
@endif
