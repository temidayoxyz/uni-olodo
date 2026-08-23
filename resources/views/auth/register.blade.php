<x-layout.guest title="Create applicant account">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Begin your application</h1>
    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
        Create an applicant account to apply for admission. You can save your progress
        and return at any time before submitting.
    </p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <x-ui.input label="Full name" name="name" required autofocus :value="old('name')"
                    autocomplete="name" placeholder="e.g. Adaeze Okonkwo" />

        <x-ui.input label="Email address" name="email" type="email" required :value="old('email')"
                    autocomplete="email" hint="Use an address you check regularly — admission decisions are sent here." />

        <x-ui.input label="Password" name="password" type="password" required
                    autocomplete="new-password" hint="At least 8 characters." />

        <x-ui.input label="Confirm password" name="password_confirmation" type="password" required
                    autocomplete="new-password" />

        <button type="submit" class="btn-primary btn-lg w-full">Create account</button>
    </form>

    <p class="mt-6 text-sm text-ink-soft">
        Already have an account? <a href="{{ route('login') }}" class="font-medium text-pine hover:underline">Sign in</a>
    </p>
</x-layout.guest>
