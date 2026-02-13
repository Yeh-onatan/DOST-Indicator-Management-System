<x-layouts.auth>
    <div class="min-h-screen flex flex-col items-center justify-start pt-16 p-4">

        {{-- LOGO (Header) --}}
        <div class="flex flex-col items-center mb-8">
            <img
                src="{{ asset('DOST Logo.png') }}"
                alt="DOST"
                class="h-40 w-auto select-none"
                draggable="false"
            />
            <p class="-mt-3 text-lg font-semibold" style="color: #00AEEF;">
                Indicators Management System
            </p>
        </div>

        <div class="w-full max-w-md">
            <div class="mb-4 @if($isExpired) bg-yellow-50 border border-yellow-200 rounded-lg p-4 @endif">
                @if($isExpired)
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-800">Password Expired</h3>
                            <p class="text-sm text-yellow-700 mt-1">Your password has expired. You must change it to continue using the system.</p>
                        </div>
                    </div>
                @elseif($daysUntilExpiry !== null && $daysUntilExpiry <= 14)
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-800">Password Expiring Soon</h3>
                            <p class="text-sm text-blue-700 mt-1">Your password will expire in {{ $daysUntilExpiry }} day(s). Consider changing it now.</p>
                        </div>
                    </div>
                @endif
            </div>

            <h1 class="text-2xl font-bold text-center mb-2" style="color: #003B5C;">Change Password</h1>
            <p class="text-center text-gray-600 mb-6 text-sm">Enter your current password and a new password to continue.</p>

            <div class="p-0 space-y-6">
                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="rounded-lg p-3 text-sm bg-red-50 text-red-700 border border-red-200">
                        <ul class="list-disc ms-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Flash status --}}
                @if (session('status'))
                    <div class="rounded-lg p-3 text-sm bg-green-50 text-green-700 border border-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                    @csrf

                    <flux:input
                        name="current_password"
                        label="Current Password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your current password"
                        viewable
                    />

                    <flux:input
                        name="password"
                        label="New Password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Enter your new password"
                        viewable
                    />

                    <flux:input
                        name="password_confirmation"
                        label="Confirm New Password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your new password"
                        viewable
                    />

                    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 border border-gray-200">
                        <p class="font-semibold mb-2">Password Requirements:</p>
                        <ul class="space-y-1 text-xs">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Minimum 12 characters
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Uppercase and lowercase letters
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                At least one number
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                At least one special character
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Not used in your last 4 passwords
                            </li>
                        </ul>
                    </div>

                    <flux:button variant="primary" type="submit" class="w-full">
                        Change Password
                    </flux:button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Force password change page to always load in DOST light theme
        (function () {
            const root = document.documentElement;
            root.classList.remove('theme-neutral','theme-dark','dark');
            root.classList.add('theme-neutral');
            localStorage.setItem('dost_theme', 'light');
        })();
    </script>
</x-layouts.auth>
