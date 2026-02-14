@php
    $orgName = \App\Models\AdminSetting::query()->value('org_name') ?: 'DOST Management Indicator Systems';
    $orgAccent = \App\Models\AdminSetting::query()->value('theme_accent');
    $orgTz = \App\Models\AdminSetting::query()->value('timezone');
@endphp
@props(['title' => $orgName])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="theme-neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    {{-- Fonts (async load to prevent blocking) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,600,700,800" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript><link href="https://fonts.bunny.net/css?family=instrument-sans:400,600,700,800" rel="stylesheet" /></noscript>

    {{-- Styles --}}
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($orgAccent)
    <style>
      :root, .theme-neutral, .theme-dark {
        --accent: {{ $orgAccent }};
        --title-accent: {{ $orgAccent }};
        --color-accent: {{ $orgAccent }};
      }
    </style>
    @endif

    {{-- Theme init: default to dark; values: light|dark. --}}
    <script>
      (function () {
        // Canonical names
        const MAP = { light: 'theme-neutral', dark: 'theme-dark' };
        const root = document.documentElement;
        // Migrate old values (white/black) to new names (light/dark)
        let saved = localStorage.getItem('dost_theme');
        if (saved === 'white') { saved = 'light'; localStorage.setItem('dost_theme','light'); }
        if (saved === 'black') { saved = 'dark';  localStorage.setItem('dost_theme','dark'); }

        function applyTheme(name) {
          root.classList.remove('theme-neutral','theme-dark','dark');
          const cls = MAP[name] || MAP.dark;
          root.classList.add(cls);
          if (name === 'dark' || !MAP[name]) root.classList.add('dark');
        }
        window.setTheme = function(name){
          localStorage.setItem('dost_theme', name);
          applyTheme(name);
        }
        // Default to light (DOST theme) if nothing saved
        applyTheme(saved || 'light');

        // Apply density from user settings on initial load (after DOM is ready)
        const density = (function(){ try { return JSON.parse('@json(auth()->user()?->settings?->density ?? "comfortable")'); } catch(e) { return 'comfortable'; } })();
        function applyDensity(name){
          var bodyEl = document.body;
          if (!bodyEl) return;
          bodyEl.classList.remove('text-sm','leading-snug','text-base','leading-normal');
          if (name === 'compact') {
            bodyEl.classList.add('text-sm','leading-snug');
          } else {
            bodyEl.classList.add('text-base','leading-normal');
          }
        }
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', function(){
            try { applyDensity(density); } catch(_) {}
          });
        } else {
          try { applyDensity(density); } catch(_) {}
        }
      })();
    </script>
</head>

{{-- Global compact baseline: smaller default text & tighter line-height --}}
<body class="min-h-screen bg-[var(--bg)] antialiased text-base leading-normal overflow-x-hidden">
    {{-- Sidebar Peek Zone: invisible strip on left edge, shown only when sidebar is closed --}}
    <div id="sidebar-peek-zone" class="fixed left-0 top-0 bottom-0 w-6 z-[95] hidden" 
         onmouseenter="showSidebarPeek()"></div>
    
    {{-- Sidebar Peek Overlay: clickable strip that opens the sidebar --}}
    <div id="sidebar-peek" class="fixed left-0 top-0 bottom-0 w-16 bg-white/95 backdrop-blur-sm border-r border-slate-200 shadow-lg z-[96] opacity-0 -translate-x-full transition-all duration-200 pointer-events-none flex flex-col items-center pt-6 cursor-pointer"
         onclick="openSidebar()" onmouseenter="showSidebarPeek()" onmouseleave="hideSidebarPeek()">
        <img src="{{ asset('/DOST Logo.png') }}" alt="DOST" class="h-10 w-auto object-contain opacity-60 mb-4">
        <div class="mt-auto mb-6 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </div>

    {{-- Floating cursor-follow button: appears on left edge hover when sidebar is closed --}}
    <button id="sidebar-peek-btn" onclick="openSidebar()" 
        class="fixed left-12 z-[97] p-2 rounded-full bg-[#003B5C] text-white shadow-xl opacity-0 pointer-events-none transition-opacity duration-200 hover:bg-[#00AEEF] hover:scale-110"
        style="top: 50%; transform: translateY(-50%);"
        onmouseenter="showSidebarPeek()" onmouseleave="hideSidebarPeek()">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    <div id="app-container" class="flex min-h-screen transition-all duration-300 pl-64">

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed left-0 top-0 bottom-0 w-64 border-r border-slate-200 p-4 flex flex-col justify-between bg-white transition-all duration-300 ease-in-out z-[90] shadow-sm">
            <div class="flex flex-col h-full">
                {{-- LOGO SECTION with collapse button --}}
                <div class="pb-6 mb-2 border-b border-slate-50 flex items-center justify-between">
                    <div class="flex-1 flex justify-center">
                        <img src="{{ asset('/DOST Logo.png') }}" alt="DOST Logo" class="h-14 w-auto object-contain">
                    </div>
                    <button id="sidebar-collapse-btn" onclick="toggleSidebar()" 
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all duration-200 flex-shrink-0" 
                        title="Collapse sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto pt-4 scrollbar-hide">
                    @auth
                        {{-- Home --}}
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200
                                  {{ request()->routeIs('dashboard')
                                      ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                            <x-icon name="home" class="w-5 h-5" />
                            <span>Dashboard</span>
                        </a>

                        {{-- Admin Panel --}}
                        @if (Auth::user()->role === \App\Models\User::ROLE_ADMIN || Auth::user()->role === \App\Models\User::ROLE_SUPER_ADMIN)
                            <div class="pt-4 pb-2">
                                <span class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Administration</span>
                            </div>
                            
                            <a href="{{ route('admin.manage') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200
                                      {{ request()->routeIs('admin.manage')
                                          ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                <x-icon name="building" class="w-5 h-5" />
                                <span>Admin Panel</span>
                            </a>

                            <a href="{{ route('admin.approvals') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200
                                      {{ request()->routeIs('admin.approvals')
                                          ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                <x-icon name="check-circle" class="w-5 h-5" />
                                <span>Approvals</span>
                            </a>

                            <a href="{{ route('admin.audit') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200
                                      {{ request()->routeIs('admin.audit')
                                          ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                                <x-icon name="eye" class="w-5 h-5" />
                                <span>Audit Logs</span>
                            </a>

                            {{-- SuperAdmin Only: Manage Users --}}
                            @if(Auth::user()->isSuperAdmin())
                                <a href="{{ route('superadmin.users') }}"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200 mt-2
                                           {{ request()->routeIs('superadmin.users')
                                               ? 'bg-purple-600 text-white shadow-md shadow-purple-100'
                                               : 'text-slate-600 hover:bg-purple-50 hover:text-purple-600' }}">
                                    <x-icon name="users" class="w-5 h-5" />
                                    <span>Manage Users</span>
                                </a>
                            @endif
                        @endif

                        <div class="pt-4 pb-2 border-t border-slate-50 mt-4">
                            <span class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Account</span>
                        </div>

                        {{-- Notifications --}}
                        <a href="{{ route('notifications.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200
                                  {{ request()->routeIs('notifications.index')
                                      ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                            <x-icon name="bell" class="w-5 h-5" />
                            <span>Notifications</span>
                        </a>

                        {{-- Settings --}}
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all duration-200
                                  {{ request()->routeIs('profile.edit')
                                      ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600' }}">
                            <x-icon name="settings" class="w-5 h-5" />
                            <span>Settings</span>
                        </a>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}" class="mt-4 pt-4 border-t border-slate-50">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-red-500 hover:bg-red-50 transition-all duration-200 group">
                                <x-icon name="logout" class="w-5 h-5 group-hover:transform group-hover:translate-x-1 duration-200" />
                                <span>Sign Out</span>
                            </button>
                        </form>
                    @endauth
                </nav>
            </div>
        </aside>

        {{-- Main content --}}
        <main id="main-content" class="flex-1 min-w-0 bg-[#F8FAFC]">
            {{-- Impersonation Banner --}}
            @if(session()->has('impersonator_id'))
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4 shadow-lg flex items-center justify-between sticky top-0 z-40">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <x-icon name="users" class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <p class="font-bold">Impersonation Mode Active</p>
                            <p class="text-purple-100 text-sm">Logged in as <strong>{{ Auth::user()->name }}</strong></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-purple-100 text-xs hidden sm:inline">Original: {{ session('impersonator_name') }}</span>
                        <a href="{{ route('superadmin.exit-impersonation') }}" 
                           class="bg-white text-purple-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-purple-50 transition shadow-sm">
                            Exit Impersonation
                        </a>
                    </div>
                </div>
            @endif

            <div class="p-6 md:p-8 lg:p-10 max-w-[1600px] mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
    @fluxScripts
    <script>
      // Sidebar Toggle Functionality
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const appContainer = document.getElementById('app-container');
        const peekZone = document.getElementById('sidebar-peek-zone');
        const isClosed = sidebar.classList.contains('-translate-x-full');

        if (isClosed) {
          openSidebar();
        } else {
          closeSidebar();
        }
      }

      function openSidebar() {
        const sidebar = document.getElementById('sidebar');
        const appContainer = document.getElementById('app-container');
        const peekZone = document.getElementById('sidebar-peek-zone');
        const peek = document.getElementById('sidebar-peek');
        const btn = document.getElementById('sidebar-peek-btn');

        sidebar.classList.remove('-translate-x-full');
        appContainer.classList.add('pl-64');
        appContainer.classList.remove('pl-0');
        peekZone.classList.add('hidden');
        
        // Immediately hide peek elements when opening sidebar
        if (peek) {
          peek.classList.add('-translate-x-full', 'opacity-0', 'pointer-events-none');
          peek.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
        }
        if (btn) {
          btn.classList.add('opacity-0', 'pointer-events-none');
          btn.classList.remove('opacity-100', 'pointer-events-auto');
        }
        
        localStorage.setItem('sidebarState', 'open');
      }

      function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const appContainer = document.getElementById('app-container');
        const peekZone = document.getElementById('sidebar-peek-zone');

        sidebar.classList.add('-translate-x-full');
        appContainer.classList.remove('pl-64');
        appContainer.classList.add('pl-0');
        peekZone.classList.remove('hidden');
        localStorage.setItem('sidebarState', 'closed');
      }

      // Peek on hover (left edge)
      let peekTimeout = null;
      function isSidebarClosed() {
        const sidebar = document.getElementById('sidebar');
        return sidebar ? sidebar.classList.contains('-translate-x-full') : false;
      }

      function showSidebarPeek() {
        if (!isSidebarClosed()) return;
        clearTimeout(peekTimeout);
        const peek = document.getElementById('sidebar-peek');
        const btn = document.getElementById('sidebar-peek-btn');
        if (!peek || !btn) return;
        peek.classList.remove('-translate-x-full', 'opacity-0', 'pointer-events-none');
        peek.classList.add('translate-x-0', 'opacity-100', 'pointer-events-auto');
        btn.classList.remove('opacity-0', 'pointer-events-none');
        btn.classList.add('opacity-100', 'pointer-events-auto');
      }

      function hideSidebarPeek(force = false) {
        clearTimeout(peekTimeout);
        peekTimeout = setTimeout(() => {
          const peek = document.getElementById('sidebar-peek');
          const btn = document.getElementById('sidebar-peek-btn');
          if (!peek || !btn) return;
          if (!force) {
            if (!isSidebarClosed()) return;
            // Don't hide if mouse is still over peek area or button
            if (peek.matches(':hover') || btn.matches(':hover')) return;
          }
          peek.classList.add('-translate-x-full', 'opacity-0', 'pointer-events-none');
          peek.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
          btn.classList.add('opacity-0', 'pointer-events-none');
          btn.classList.remove('opacity-100', 'pointer-events-auto');
        }, 500);
      }

      // Follow cursor Y position for the peek button
      document.addEventListener('mousemove', function(e) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('-translate-x-full') && e.clientX < 80) {
          const btn = document.getElementById('sidebar-peek-btn');
          if (btn) {
            const y = Math.max(40, Math.min(e.clientY, window.innerHeight - 40));
            btn.style.top = y + 'px';
            btn.style.transform = 'translateY(-50%)';
          }
        }
      });

      // Initialize sidebar state on page load
      document.addEventListener('DOMContentLoaded', function() {
        const sidebarState = localStorage.getItem('sidebarState');
        if (sidebarState === 'closed') {
          closeSidebar();
        } else {
          openSidebar();
        }

        // --- Page Fade-In Transition ---
        // Skip animation when the user navigates to the same page they're already on.
        const currentPath = window.location.pathname + window.location.search;
        const lastPath    = sessionStorage.getItem('_lastPage');

        const mainContent = document.getElementById('main-content');
        if (mainContent && currentPath !== lastPath) {
          mainContent.classList.add('page-fade-in');
          // Remove the class after animation ends so it doesn't replay on Livewire updates
          mainContent.addEventListener('animationend', function handler() {
            mainContent.classList.remove('page-fade-in');
            mainContent.removeEventListener('animationend', handler);
          });
        }
        sessionStorage.setItem('_lastPage', currentPath);
      });

      // Apply settings after saving in Preferences
      window.addEventListener('apply-theme', function (e) {
        const pref = (e && e.detail && e.detail.value) ? e.detail.value : 'dark';
        if (window.setTheme) window.setTheme(pref);
      });
      window.addEventListener('apply-density', function (e) {
        const d = (e && e.detail && e.detail.value) ? e.detail.value : 'comfortable';
        const body = document.body;
        body.classList.remove('text-sm','leading-snug','text-base','leading-normal');
        if (d === 'compact') {
          body.classList.add('text-sm','leading-snug');
        } else {
          body.classList.add('text-base','leading-normal');
        }
      });

      // Keyboard Shortcuts (QOL feature)
      document.addEventListener('keydown', function(e) {
        // Don't trigger if user is typing in an input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
          // Exception: If in search input and pressed Escape, blur it
          if (e.key === 'Escape') {
            e.target.blur();
          }
          return;
        }

        // "/" - Focus search input
        if (e.key === '/' && !e.ctrlKey && !e.altKey && !e.metaKey) {
          e.preventDefault();
          const searchInput = document.querySelector('input[type="text"][placeholder*="Search"], input[wire\\:model*="search"]');
          if (searchInput) {
            searchInput.focus();
            searchInput.select();
          }
        }

        // "n" or "N" - Open create indicator modal (if create button exists)
        if ((e.key === 'n' || e.key === 'N') && !e.ctrlKey && !e.altKey && !e.metaKey) {
          e.preventDefault();
          const createBtn = document.querySelector('button[wire\\:click="openCreate"]');
          if (createBtn) {
            createBtn.click();
          }
        }

        // "Escape" - Close modals / clear focus
        if (e.key === 'Escape') {
          document.activeElement.blur();
        }

        // "?" - Show keyboard shortcuts help (optional for future)
        if (e.key === '?' && !e.ctrlKey && !e.altKey && !e.metaKey) {
          e.preventDefault();
          alert('Keyboard Shortcuts:\n\n/ - Focus search\nN - New indicator\nEsc - Close/blur\n\n(More shortcuts coming soon!)');
        }
      });


    </script>
</body>
</html>
