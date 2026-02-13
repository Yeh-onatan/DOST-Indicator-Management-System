<x-layouts.auth>
    {{-- Main container: Logo at the top, content starts below it --}}
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

        {{-- Main content area (Form is now transparent/boxless) --}}
        <div class="w-full max-w-md mt--1"> 
            
            {{-- REMOVED: The div containing the H1 heading and the descriptive paragraph --}}

            <div class="p-0 space-y-10">
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
                <x-auth-session-status class="text-center" :status="session('status')" />

                {{-- Form --}}
                {{-- ADDED: mt-6 to push the form down slightly since the header text was removed --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5 mt-6">
                    @csrf

                    <flux:input
                        name="username"
                        :label="__('Username')"
                        type="text"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Username"
                        value="{{ old('username') }}"
                    />

                    <div class="space-y-2">
                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            :placeholder="__('Password')"
                            viewable
                        />
                        {{-- Optional: forgot link (uncomment when route exists) --}}
                        {{-- <a href="{{ route('password.request') }}" class="text-xs text-[var(--accent)] hover:underline">Forgot password?</a> --}}
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                class="w-4 h-4 rounded cursor-pointer"
                                style="border: 2px solid #00AEEF; accent-color: #00AEEF; background-color: #FFFFFF;"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span class="text-sm font-medium" style="color: #003B5C;">{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                        {{ __('Log in') }}
                    </flux:button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Force /login to always load in DOST light theme
        (function () {
            const root = document.documentElement;
            root.classList.remove('theme-neutral','theme-dark','dark');
            root.classList.add('theme-neutral');
            localStorage.setItem('dost_theme', 'light');
        })();
    </script>
</x-layouts.auth>
