<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="login-url" content="{{ route('login') }}">
    <meta name="gallery-download-endpoint" content="{{ route('gallery.download-image') }}">
    <title>{{ config('app.name', 'eGStudio_AI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('vite-scripts')
    
    {{-- CUSTOM STYLES TO HIDE SIDEBAR SCROLLBAR --}}
    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        [x-cloak] { display: none !important; }
    </style>
</head>

@php
    $hideSidebar = auth()->check() && auth()->user()->isApprover();
@endphp

<body class="font-sans antialiased text-gray-100 bg-black flex h-screen">

    {{-- SIDEBAR NAVIGATION (hidden for approver accounts — they use Approval Center only) --}}
    @unless($hideSidebar)
    <aside class="w-64 bg-[#0a0a0a] border-r border-white/5 hidden sm:flex sm:flex-col shrink-0">

        {{-- Logo Area --}}
        <div class="h-16 flex items-center px-6 border-b border-white/5">
            <span class="text-2xl font-black tracking-tighter text-white">eGStudio<span
                    class="text-blue-500">AI</span></span>
        </div>

        {{-- Navigation Links (Applied hide-scrollbar here) --}}
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto hide-scrollbar">

            {{-- Core Dashboard (Usually everyone sees this) --}}
            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
                <span>Dashboard</span>
            </a>

            {{-- APPROVAL CENTER (Approver accounts only) --}}
            @if(auth()->check() && auth()->user()->isApprover())
                @php
                    $pendingApprovals = \App\Http\Controllers\ApprovalController::pendingCountForApprover(auth()->user());
                @endphp
                <a href="{{ route('approvals.index') }}"
                    class="{{ request()->routeIs('approvals.*') ? 'bg-emerald-600/10 text-emerald-400 border-emerald-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} flex items-center justify-between gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Approval Center</span>
                    </span>
                    @if($pendingApprovals > 0)
                        <span class="px-1.5 py-0.5 bg-amber-500 text-black text-[9px] font-black rounded-full">{{ $pendingApprovals }}</span>
                    @endif
                </a>
            @endif

            @php
                $promoNavActive = request()->routeIs('cgi.*');
                $occNavActive = request()->routeIs('occasions.*');
                $showPromoNav = auth()->user()?->can('access_cgi_generator')
                    || auth()->user()?->can('view_cgi_index')
                    || auth()->user()?->can('view_video_gallery')
                    || auth()->user()?->can('view_image_gallery');
                $subLinkActive = 'bg-blue-600/10 text-blue-400 border-blue-500/20';
                $subLinkIdle = 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent';
                $subLinkActivePink = 'bg-pink-600/10 text-pink-400 border-pink-500/20';
            @endphp

            {{-- Asset Library (shared — not under Promotional / Occasional) --}}
            @can('access_cgi_generator')
                <a href="{{ route('assets.index') }}"
                    class="{{ request()->routeIs('assets.*') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                    <span>Asset Library</span>
                </a>
            @endcan

            {{-- PROMOTIONAL CONTENT (CGI Studio) --}}
            @if($showPromoNav)
                <div x-data="{ open: @json($promoNavActive) }" class="space-y-1">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest
                                   {{ $promoNavActive ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200 border-transparent' }}">
                        <span class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="truncate text-left">Promotional Content</span>
                        </span>
                        <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak class="ml-3 pl-3 border-l border-white/10 space-y-1 pb-1">
                        @can('access_cgi_generator')
                            <a href="{{ route('cgi.create') }}"
                               class="{{ request()->routeIs('cgi.create') ? $subLinkActive : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                                <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>New CGI Directive</span>
                            </a>
                        @endcan

                        @can('view_cgi_index')
                            <a href="{{ route('cgi.index') }}"
                               class="{{ request()->routeIs('cgi.index') ? $subLinkActive : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                                <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                                <span>Directive Studio</span>
                            </a>
                        @endcan

                        @can('view_video_gallery')
                            <a href="{{ route('cgi.videos') }}"
                               class="{{ request()->routeIs('cgi.videos') ? $subLinkActive : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                                <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <span>Video Gallery</span>
                            </a>
                        @endcan

                        @can('view_image_gallery')
                            <a href="{{ route('cgi.images') }}"
                               class="{{ request()->routeIs('cgi.images') ? $subLinkActive : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                                <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Image Gallery</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endif

            {{-- OCCASIONAL CONTENT (Occasion Studio) --}}
            @can('view_occasions')
                <div x-data="{ open: @json($occNavActive) }" class="space-y-1">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest
                                   {{ $occNavActive ? 'bg-pink-600/10 text-pink-400 border-pink-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200 border-transparent' }}">
                        <span class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="truncate text-left">Occasional Content</span>
                        </span>
                        <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak class="ml-3 pl-3 border-l border-white/10 space-y-1 pb-1">
                        <a href="{{ route('occasions.create') }}"
                           class="{{ request()->routeIs('occasions.create') ? $subLinkActivePink : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Occasion Directive</span>
                        </a>

                        <a href="{{ route('occasions.index') }}"
                           class="{{ request()->routeIs('occasions.index') ? $subLinkActivePink : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Occasion Studio</span>
                        </a>

                        <a href="{{ route('occasions.gallery') }}"
                           class="{{ request()->routeIs('occasions.gallery') ? $subLinkActivePink : $subLinkIdle }} flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all text-[10px] font-bold uppercase tracking-widest">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Occasion Gallery</span>
                        </a>
                    </div>
                </div>
            @endcan

            {{-- ADMIN ONLY SECTION (Protected by Database Column) --}}
            @if(auth()->check() && auth()->user()->role === 'admin')
                <div class="pt-8 pb-2">
                    <p class="px-4 text-[9px] font-black text-purple-500 uppercase tracking-[0.2em]">System Admin</p>
                </div>

                {{-- Agent Roster --}}
                <a href="{{ route('admin.users.index') }}"
                    class="{{ request()->routeIs('admin.users.*') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                    <span>Agent Roster</span>
                </a>

                {{-- System Roles --}}
                <a href="{{ route('admin.roles.index') }}"
                    class="{{ request()->routeIs('admin.roles.*') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    <span>System Roles</span>
                </a>

                {{-- Neural Pipeline Audit --}}
                <a href="{{ route('admin.cgi_audit.index') }}"
                    class="{{ request()->routeIs('admin.cgi_audit.*') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                    <span>Neural Audit</span>
                </a>

                {{-- Monetization Plans (single "pic plan" covering CGI & Occasion Studio) --}}
                <a href="{{ route('admin.packages.index') }}"
                    class="{{ request()->routeIs('admin.packages.*') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }} w-full flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Monetization Plans</span>
                </a>

            @endif

            {{-- SaaS Monetization Links --}}
            @can('subscribe_to_packages')
                {{-- 1. Buy Packages Link --}}
                <a href="{{ route('pricing.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest {{ request()->routeIs('pricing') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                    <span>Buy Credits</span>
                </a>
            @endcan

            {{-- 2. User Billing/Invoice Dashboard Link --}}
            @can('view_billing')
                <a href="{{ route('billing.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg border transition-all text-[11px] font-bold uppercase tracking-widest {{ request()->routeIs('billing.index') ? 'bg-emerald-600/10 text-emerald-400 border-emerald-500/20 shadow-lg' : 'text-gray-500 hover:bg-white/5 hover:text-gray-300 border-transparent' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <span>My Subscription</span>
                </a>
            @endcan

        </nav>
    </aside>
    @endunless

    {{-- MAIN CONTENT AREA --}}
    <div class="flex-1 flex flex-col overflow-hidden bg-[#050505]">

        {{-- Top Navigation Bar (Profile, Logout, etc) --}}
        @include('layouts.navigation')

        {{-- Page Content Slot --}}
        <main class="flex-1 overflow-y-auto hide-scrollbar">
            {{ $slot }}
        </main>

    </div>

    {{-- Keep session + CSRF fresh while users work on long studio forms --}}
    @auth
    <script>
        (function () {
            const keepAliveUrl = @json(route('session.keep-alive'));
            const intervalMs = 1000 * 60 * 15; // 15 minutes

            async function pingSession() {
                try {
                    const response = await fetch(keepAliveUrl, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.status === 419 || response.status === 401) {
                        window.redirectToLoginAfterSessionExpired?.();
                        return;
                    }

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    window.refreshCsrfToken?.(data.csrf_token);
                } catch (error) {
                    // Network blip — ignore; next ping will retry
                }
            }

            pingSession();
            setInterval(pingSession, intervalMs);
        })();
    </script>
    @endauth

    {{-- Global Loading Handler --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle all form submissions that don't have preventDefault() in their onsubmit
            document.body.addEventListener('submit', function (e) {
                if (!e.defaultPrevented) {
                    window.dispatchEvent(new CustomEvent('loading'));
                }
            });

            // Handle clicks on elements with data-loading attribute
            document.body.addEventListener('click', function (e) {
                if (e.target.matches('[data-loading]')) {
                    window.dispatchEvent(new CustomEvent('loading'));
                }
            });

            // When navigating back, browser might show the page from cache with the overlay visible
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.dispatchEvent(new CustomEvent('loading-done'));
                }
            });
        });
    </script>
</body>

</html>