@props([
    'show' => false,
    'usersForManagement' => null,
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
       <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[80vh] overflow-hidden flex flex-col"
            @click.away="open = false; $wire.closeUserManagementPanel()">

           {{-- Header --}}
           <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-purple-600">
               <div class="flex items-center gap-3">
                   <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                       <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                       </svg>
                   </div>
                   <div>
                       <h3 class="text-lg font-bold text-white">User Management</h3>
                       <p class="text-purple-200 text-xs">SuperAdmin Panel - Impersonate & Manage Users</p>
                   </div>
               </div>
               <button wire:click="closeUserManagementPanel()"
                       class="text-white/80 hover:text-white transition">
                   <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                   </svg>
               </button>
           </div>

           {{-- Search Bar --}}
           <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
               <div class="relative">
                   <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                   </svg>
                   <input wire:model.live.debounce.300ms="userSearch"
                          type="text"
                          placeholder="Search users by name, username, or email..."
                          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
               </div>
           </div>

           {{-- User List --}}
           <div class="flex-1 overflow-y-auto p-6">
               @if(auth()->user()->isSuperAdmin() && $usersForManagement && $usersForManagement->count() > 0)
                   <div class="space-y-3">
                       @foreach($usersForManagement as $userItem)
                           <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition bg-white">
                               <div class="flex items-center justify-between">
                                   {{-- User Info --}}
                                   <div class="flex items-center gap-4">
                                       <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                           <span class="text-purple-700 font-bold text-lg">{{ strtoupper(substr($userItem->name, 0, 1)) }}</span>
                                       </div>
                                       <div>
                                           <div class="font-semibold text-gray-900">{{ $userItem->name }}</div>
                                           <div class="text-sm text-gray-500">@{{ $userItem->username }}</div>
                                           <div class="flex items-center gap-2 mt-1">
                                               <span class="px-2 py-0.5 rounded text-xs font-medium
                                                   {{ $userItem->role === \App\Models\User::ROLE_SUPER_ADMIN ? 'bg-purple-100 text-purple-700' :
                                                      ($userItem->role === \App\Models\User::ROLE_ADMIN ? 'bg-orange-100 text-orange-700' :
                                                      ($userItem->role === \App\Models\User::ROLE_EXECOM ? 'bg-yellow-100 text-yellow-700' :
                                                      'bg-gray-100 text-gray-700')) }}">
                                                   {{ ucfirst(str_replace('_', ' ', $userItem->role)) }}
                                               </span>
                                               @if($userItem->is_locked ?? false)
                                                   <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Locked</span>
                                               @endif
                                           </div>
                                       </div>
                                   </div>

                                   {{-- Actions --}}
                                   <div class="flex items-center gap-2">
                                       @if($userItem->id !== auth()->id())
                                           {{-- Impersonate Button --}}
                                           <button wire:click="impersonateUser({{ $userItem->id }})"
                                                   wire:confirm="Impersonate {{ $userItem->name }}?"
                                                   class="px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                               </svg>
                                               Impersonate
                                           </button>
                                       @else
                                           <span class="px-3 py-2 bg-gray-100 text-gray-500 text-sm font-medium rounded-lg">You</span>
                                       @endif
                                   </div>
                               </div>

                               {{-- Additional Info --}}
                               <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500 flex items-center gap-4">
                                   @if($userItem->office)
                                       <span>Office: {{ $userItem->office->name }}</span>
                                   @endif
                                   @if($userItem->region)
                                       <span>Region: {{ $userItem->region->name }}</span>
                                   @endif
                                   @if($userItem->agency)
                                       <span>Agency: {{ $userItem->agency->name }}</span>
                                   @endif
                               </div>
                           </div>
                       @endforeach
                   </div>
               @else
                   <div class="text-center py-12 text-gray-500">
                       <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                       </svg>
                       <p>No users found.</p>
                   </div>
               @endif
           </div>
       </div>
   </div>
</div>
@endif
