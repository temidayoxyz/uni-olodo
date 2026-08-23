{{-- Guest/auth shell: focused split layout with institutional context --}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — University of Olodo' : 'Sign in — University of Olodo' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-pine focus:px-4 focus:py-2 focus:text-white">
        Skip to content
    </a>

    <div class="grid min-h-dvh lg:grid-cols-[1.1fr_1fr]">
        {{-- Brand panel (desktop) --}}
        <aside class="relative hidden flex-col justify-between bg-pine p-10 text-white lg:flex">
            <a href="{{ url('/') }}" class="flex items-center gap-3 text-white/90" tabindex="-1" aria-hidden="true">
                <x-brand-mark class="size-9" />
                <span class="font-display text-xl leading-none font-semibold tracking-tight">
                    University of<br/>Olodo
                </span>
            </a>

            <blockquote class="max-w-md">
                <p class="font-display text-3xl leading-snug font-medium tracking-tight">
                    “Knowledge. Character. Impact.”
                </p>
                <footer class="mt-4 text-sm text-white/70">The motto we hold ourselves to — in the classroom and on this platform.</footer>
            </blockquote>

            <p class="text-xs text-white/60">
                Olado, Ibadan, Oyo State · info@olodo.edu.ng
            </p>
        </aside>

        {{-- Form column --}}
        <main id="main" class="flex flex-col px-5 py-8 sm:px-10">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 self-start text-pine lg:hidden">
                <x-brand-mark class="size-8" />
                <span class="font-display text-lg leading-none font-semibold tracking-tight">University of Olodo</span>
            </a>

            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-10">
                {{ $slot }}
            </div>

            <p class="mx-auto w-full max-w-sm text-xs text-ink-faint">
                Need help? Visit the
                <a href="/contact" class="font-medium text-pine hover:underline">help centre</a> or email
                support@olodo.edu.ng
            </p>
        </main>
    </div>
</body>
</html>
