{{-- Shared shell for HTTP error pages --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — University of Olodo</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-paper">
    <main class="flex min-h-dvh flex-col items-center justify-center px-6 text-center">
        <a href="{{ url('/') }}" class="mb-8 flex items-center gap-2.5 text-pine">
            <x-brand-mark class="size-9" />
            <span class="font-display text-lg leading-none font-semibold tracking-tight">University of Olodo</span>
        </a>

        <p class="font-display text-7xl font-semibold tracking-tight text-pine/25 tabular-nums">{{ $code }}</p>
        <h1 class="mt-3 max-w-md text-balance font-display text-2xl font-semibold tracking-tight">{{ $title }}</h1>
        <p class="mt-3 max-w-md leading-relaxed text-ink-soft">{{ $message }}</p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth
                <a href="{{ \App\Support\PortalUrl::homeFor(auth()->user()) }}" class="btn-primary">Back to my portal</a>
            @elseauth
                <a href="{{ route('home') }}" class="btn-primary">Back to the homepage</a>
                <a href="{{ route('contact') }}" class="btn-secondary">Contact support</a>
            @endauth
        </div>
    </main>
</body>
</html>
