<x-layout.guest title="Verify your email">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Verify your email address</h1>

    <div class="mt-6 space-y-4">
        @if (session('status') === 'verification-link-sent')
            <x-ui.alert type="success">A fresh verification link has been sent to your email address.</x-ui.alert>
        @endif

        <p class="text-sm leading-relaxed text-ink-soft">
            Before you can begin your application, confirm that
            <strong class="text-ink">{{ auth()->user()->email }}</strong> belongs to you.
            Open the verification link we sent when you created your account.
        </p>

        <p class="text-sm leading-relaxed text-ink-soft">
            Didn't receive it? Check your spam folder, then request a new link below.
            Links expire after 60 minutes.
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-secondary">Resend verification email</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-ghost">Sign out</button>
            </form>
        </div>
    </div>
</x-layout.guest>
