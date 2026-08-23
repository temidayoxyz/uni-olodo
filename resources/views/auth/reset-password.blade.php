<x-layout.guest title="Set new password">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Choose a new password</h1>
    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
        You're setting a new password for <strong>{{ $request->email }}</strong>.
    </p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <input type="hidden" name="email" value="{{ $request->email ?? old('email') }}">

        <x-ui.input label="New password" name="password" type="password" required
                    autocomplete="new-password" hint="At least 8 characters." />

        <x-ui.input label="Confirm new password" name="password_confirmation" type="password" required
                    autocomplete="new-password" />

        <button type="submit" class="btn-primary btn-lg w-full">Reset password</button>
    </form>
</x-layout.guest>
