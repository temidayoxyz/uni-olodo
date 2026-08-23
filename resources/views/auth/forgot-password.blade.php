<x-layout.guest title="Forgot password">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Reset your password</h1>
    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
        Enter the email address on your account and we'll send you a reset link.
    </p>

    @if (session('status'))
        <div class="mt-6">
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <x-ui.input label="Email address" name="email" type="email" required autofocus
                    :value="old('email')" autocomplete="username" />

        <button type="submit" class="btn-primary btn-lg w-full">Send reset link</button>
    </form>

    <p class="mt-6 text-sm text-ink-soft">
        <a href="{{ route('login') }}" class="font-medium text-pine hover:underline">← Back to sign in</a>
    </p>
</x-layout.guest>
