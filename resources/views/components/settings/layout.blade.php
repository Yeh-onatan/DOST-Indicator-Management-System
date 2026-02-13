{{-- Main Settings Title (One single title in a box) --}}
<div class="mb-6 bg-white border-2 border-black rounded-lg p-6">
    <h1 class="text-3xl font-black mb-2" style="color: #000000;">{{ __('Settings') }}</h1>
    <p class="text-base" style="color: #525252;">{{ __('Manage your profile and account settings') }}</p>
</div>

<div class="flex items-start gap-6 max-md:flex-col">
{{-- Sidebar Navigation --}}
<div class="w-full md:w-[220px]">
    <nav class="space-y-1">

        {{-- Reusable class --}}
        @php
            $active = 'bg-[#00AEEF] text-white shadow-sm';
            $inactive = 'text-[#404040] hover:bg-[#F5F5F5]';
        @endphp

        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('profile.edit') ? $active : $inactive }}">
            {{ __('Profile') }}
        </a>

        <a href="{{ route('user-password.edit') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('user-password.edit') ? $active : $inactive }}">
            {{ __('Password') }}
        </a>

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <a href="{{ route('two-factor.show') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors
                      {{ request()->routeIs('two-factor.show') ? $active : $inactive }}">
                {{ __('Two-Factor Auth') }}
            </a>
        @endif
        @php($u = auth()->user())
    </nav>
</div>


    {{-- Main Content Area in a Box --}}
    <div class="flex-1 bg-white border-2 border-black rounded-lg p-6">
        {{ $slot }}
    </div>
</div>
