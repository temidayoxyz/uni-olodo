{{-- Authenticated portal shell: role-aware sidebar (desktop), compact top bar, mobile bottom nav --}}
@props(['title' => null])
@php
    $user = auth()->user();
    $sections = \App\Support\Navigation::for($user);
    $currentPath = request()->path();
    $unread = $user->unreadNotifications()->count();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — University of Olodo' : 'Portal — University of Olodo' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-pine focus:px-4 focus:py-2 focus:text-white">
        Skip to content
    </a>

    <div class="flex min-h-dvh">
        {{-- Sidebar (tablet+) --}}
        <aside class="sticky top-0 hidden h-dvh w-60 shrink-0 flex-col border-e border-line bg-surface md:flex lg:w-64">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 px-5 py-5 text-pine">
                <x-brand-mark class="size-8" />
                <span class="font-display text-base leading-none font-semibold tracking-tight">
                    University of<br/>Olodo
                </span>
            </a>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-4" aria-label="{{ $user->role->label() }} portal">
                @foreach ($sections as $section)
                    <div>
                        @if (($section['section'] ?? null) !== null)
                            <p class="mb-1.5 px-3 text-[0.6875rem] font-semibold tracking-widest text-ink-faint uppercase">{{ $section['section'] }}</p>
                        @endif
                        <ul class="space-y-0.5">
                            @foreach ($section['items'] as $item)
                                @php $active = rtrim($currentPath, '/') === ltrim(parse_url($item['url'], PHP_URL_PATH) ?? $item['url'], '/'); @endphp
                                <li>
                                    <a href="{{ $item['url'] }}" class="nav-link {{ $active ? 'active' : '' }}"
                                       @if($active) aria-current="page" @endif>
                                        <x-lucide-{{ $item['icon'] }} class="size-[1.05rem] shrink-0 opacity-80" />
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-line-soft px-4 py-3.5">
                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                <p class="text-xs text-ink-faint">{{ $user->role->label() }}</p>
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-paper/90 px-4 backdrop-blur sm:px-6">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-pine md:hidden" aria-label="University of Olodo home">
                    <x-brand-mark class="size-7" />
                </a>

                <h2 class="min-w-0 truncate font-display text-lg font-semibold tracking-tight">
                    {{ $title ?? $user->role->label().' portal' }}
                </h2>

                <div class="ms-auto flex items-center gap-1.5">
                    {{-- Notifications --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" :aria-expanded="open"
                                class="relative inline-flex size-9 items-center justify-center rounded-full text-ink-soft hover:bg-pine-tint hover:text-pine"
                                aria-label="Notifications{{ $unread ? " ({$unread} unread)" : '' }}">
                            <x-lucide-bell class="size-5" />
                            @if ($unread)
                                <span class="absolute top-1 right-1 size-2 rounded-full bg-danger" aria-hidden="true"></span>
                                <span class="sr-only">{{ $unread }} unread</span>
                            @endif
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
                             x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             class="panel absolute end-0 z-40 mt-2 w-84 max-w-[calc(100vw-2rem)] origin-top-right shadow-lg">
                            <div class="panel-header !px-4">
                                <p class="text-sm font-semibold">Notifications</p>
                                @if ($unread)
                                    <form method="POST" action="/notifications/read-all">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-pine hover:underline">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <ul class="max-h-96 divide-y divide-line-soft overflow-y-auto">
                                @forelse ($user->notifications()->take(8) as $notification)
                                    @php $data = $notification->data; @endphp
                                    <li class="{{ $notification->unread() ? 'bg-pine-tint/40' : '' }}">
                                        <a href="{{ $data['url'] ?? '#' }}"
                                           @unless($notification->unread()) tabindex="-1" @endunless
                                           class="block px-4 py-3 hover:bg-surface-dim">
                                            <p class="flex items-start justify-between gap-3 text-sm font-medium">
                                                <span>{{ $data['title'] ?? 'Notification' }}</span>
                                                @if ($notification->unread())
                                                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-pine" aria-hidden="true"></span>
                                                @endif
                                            </p>
                                            <p class="mt-0.5 line-clamp-2 text-xs text-ink-soft">{{ $data['body'] ?? '' }}</p>
                                            <p class="mt-1 text-[0.6875rem] text-ink-faint">{{ $notification->created_at->diffForHumans() }}</p>
                                        </a>
                                    </li>
                                @empty
                                    <li class="px-4 py-8 text-center text-sm text-ink-faint">You're all caught up.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    {{-- Account menu --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" :aria-expanded="open"
                                class="flex items-center gap-2 rounded-full py-1 ps-1 pe-2 hover:bg-pine-tint"
                                aria-label="Account menu">
                            <span class="flex size-7 items-center justify-center rounded-full bg-pine text-xs font-bold text-white">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                            <x-lucide-chevron-down class="hidden size-4 text-ink-soft sm:block" />
                        </button>

                        <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
                             x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             class="panel absolute end-0 z-40 mt-2 w-56 origin-top-right py-1.5 shadow-lg">
                            <div class="px-4 pb-2">
                                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                                <p class="truncate text-xs text-ink-faint">{{ $user->email }}</p>
                            </div>
                            <div class="divider"></div>
                            <a href="/settings/profile" class="block px-4 py-2 text-sm hover:bg-surface-dim">Profile & settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 text-start text-sm text-danger hover:bg-danger-tint">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 pt-6 pb-28 md:pb-10 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            {{-- Mobile bottom navigation --}}
            <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Primary">
                @php
                    $mobileItems = collect($sections)->flatMap(fn ($s) => $s['items'])->take(4)->values();
                @endphp
                @foreach ($mobileItems as $item)
                    @php $active = rtrim($currentPath, '/') === ltrim($item['url'], '/'); @endphp
                    <a href="{{ $item['url'] }}" class="flex flex-col items-center gap-0.5 py-2.5 text-[0.625rem] font-medium {{ $active ? 'text-pine' : 'text-ink-faint' }}"
                       @if($active) aria-current="page" @endif>
                        <x-lucide-{{ $item['icon'] }} class="size-5" />
                        {{ str_replace(' ', "\n", wordwrap($item['label'], 12)) }}
                    </a>
                @endforeach
                <button x-data="{ moreOpen: false }" @click="$dispatch('toggle-more-menu')"
                        class="flex flex-col items-center gap-0.5 py-2.5 text-[0.625rem] font-medium text-ink-faint">
                    <x-lucide-menu class="size-5" />
                    More
                </button>
            </nav>

            {{-- Mobile "more" sheet: every destination, not just the first four --}}
            <div x-data="{ open: false }"
                 @toggle-more-menu.window="open = !open" @keydown.escape.window="open = false"
                 x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="All pages"
                 class="fixed inset-0 z-40 md:hidden">
                <div class="absolute inset-0 bg-ink/40" @click="open = false" aria-hidden="true"></div>
                <div class="absolute inset-x-0 bottom-0 rounded-t-2xl border-t border-line bg-surface p-4 pb-[calc(1rem+env(safe-area-inset-bottom))]"
                     x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0">
                    <p class="mb-2 px-2 text-xs font-semibold tracking-widest text-ink-faint uppercase">All pages</p>
                    <nav class="grid grid-cols-2 gap-1" aria-label="Mobile secondary">
                        @foreach (collect($sections)->flatMap(fn ($s) => $s['items']) as $item)
                            <a href="{{ $item['url'] }}" class="nav-link">{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                    <div class="divider my-3"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary w-full">Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
