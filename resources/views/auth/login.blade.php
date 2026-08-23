<x-layout.guest title="Sign in">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Sign in to the portal</h1>
    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
        Use the email address the university has on record for you.
        New applicant? <a href="{{ route('register') }}" class="font-medium text-pine hover:underline">Create an account</a> to begin your application.
    </p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-ui.input label="Email address" name="email" type="email" required autofocus
                        :value="old('email')" autocomplete="username" placeholder="you@olodo.edu.ng" />
        </div>

        <div>
            <div class="flex items-baseline justify-between">
                <label for="password" class="label">Password <span class="text-danger" aria-hidden="true"> *</span></label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-medium text-pine hover:underline">Forgot password?</a>
            </div>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                   class="input @error('password') error @enderror"
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror />
            @error('password')
                <p id="password-error" class="error-text" role="alert">
                    <x-lucide-circle-alert class="mt-px size-3.5 shrink-0" /> {{ $message }}
                </p>
            @enderror
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-soft">
            <input type="checkbox" name="remember" class="size-4 accent-[var(--color-pine)]" />
            Keep me signed in on this device
        </label>

        <button type="submit" class="btn-primary btn-lg w-full">Sign in</button>
    </form>
</x-layout.guest>
