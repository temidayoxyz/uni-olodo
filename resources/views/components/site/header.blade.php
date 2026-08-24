<header x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false"
        class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
        {{-- Brand --}}
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-pine">
            <x-brand-mark class="size-8" />
            <span class="font-display text-lg leading-none font-semibold tracking-tight">
                University of<br/>Olodo
            </span>
        </a>

        {{-- Primary navigation (desktop) --}}
        <nav class="ms-auto hidden items-center gap-6 lg:flex" aria-label="Main">
            @foreach ([
                'About' => '/about',
                'Academics' => '/academics',
                'Admissions' => '/admissions',
                'Campus Life' => '/campus-life',
                'News & Events' => '/news',
                'Resources' => '/resources',
                'Contact' => '/contact',
            ] as $label => $path)
                <a href="{{ $path }}" class="public-nav-link">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ms-auto flex items-center gap-2 lg:ms-4">
            @auth
                <a href="{{ \App\Support\PortalUrl::homeFor(auth()->user()) }}" class="btn-primary btn-sm sm:text-sm">My portal</a>
            @elseauth
                <a href="{{ route('login') }}" class="btn-secondary btn-sm sm:text-sm">Sign in</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm sm:text-sm">Apply to Olodo</a>
            @endauth

            <button @click="navOpen = !navOpen" :aria-expanded="navOpen"
                    class="inline-flex size-9 items-center justify-center rounded-[var(--radius-control)] border border-line bg-surface text-ink lg:hidden"
                    aria-controls="mobile-nav" :aria-label="navOpen ? 'Close menu' : 'Open menu'">
                <x-lucide-menu class="size-5" x-show="!navOpen" />
                <x-lucide-x class="size-5" x-cloak x-show="navOpen" />
            </button>
        </div>
    </div>

    {{-- Mobile navigation --}}
    <nav id="mobile-nav" x-show="navOpen" x-cloak aria-label="Mobile"
         x-transition:enter="transition ease-out duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
         x-transition:enter-start="-translate-y-2 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         class="border-t border-line bg-surface lg:hidden">
        <div class="mx-auto grid max-w-7xl gap-1 px-4 py-4">
            @foreach ([
                'About' => '/about',
                'Academics' => '/academics',
                'Admissions' => '/admissions',
                'Campus Life' => '/campus-life',
                'News & Events' => '/news',
                'Resources' => '/resources',
                'Contact' => '/contact',
            ] as $label => $path)
                <a href="{{ $path }}" class="rounded-[var(--radius-control)] px-3 py-2.5 text-sm font-medium hover:bg-paper-deep">{{ $label }}</a>
            @endforeach
            @guest
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-line-soft pt-4">
                    <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
                    <a href="{{ route('register') }}" class="btn-primary">Apply</a>
                </div>
            @endguest
        </div>
    </nav>
</header>
