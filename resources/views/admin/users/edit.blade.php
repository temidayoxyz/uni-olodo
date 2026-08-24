<x-layout.portal :title="'Manage — '.$user->name">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('admin.users.index') }}" class="hover:text-pine">Users & roles</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $user->name }}</span>
    </nav>

    <x-ui.page-header title="{{ $user->name }}" :subtitle="$user->email.' · joined '.$user->created_at->format('j F Y')" />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    @php
        $isStudent = $user->role === \App\Enums\UserRole::Student;
        $hasProfile = $user->studentProfile !== null;
    @endphp

    <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="panel space-y-5 px-6 py-6">
            @csrf
            @method('PUT')

            <x-ui.select label="Role" name="role" required
                        :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])"
                        :selected="$user->role->value"
                        hint="Changing a role grants or removes portal access immediately." />

            <fieldset>
                <legend class="label">Account status</legend>
                <div class="space-y-2">
                    <label class="flex items-center gap-2.5 text-sm">
                        <input type="radio" name="status" value="active" {{ old('status', $user->status->value) === 'active' ? 'checked' : '' }} class="size-4 accent-[var(--color-pine)]">
                        Active — normal access
                    </label>
                    <label class="flex items-center gap-2.5 text-sm">
                        <input type="radio" name="status" value="suspended" {{ old('status', $user->status->value) === 'suspended' ? 'checked' : '' }} class="size-4 accent-[var(--color-pine)]">
                        Suspended — sign-in blocked, existing sessions revoked at next request
                    </label>
                </div>
            </fieldset>

            <div class="flex justify-end border-t border-line-soft pt-4">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>

        <aside class="space-y-6">
            <section class="panel px-5 py-5 text-sm">
                <h2 class="text-sm font-semibold">Context</h2>
                <dl class="mt-3 space-y-2 text-ink-soft">
                    @if ($isStudent && $hasProfile)
                        <p>Matric: <span class="font-medium tabular-nums">{{ $user->studentProfile->matric_number }}</span></p>
                        <p>Programme: <span class="font-medium">{{ $user->studentProfile->programme?->name }} ({{ $user->studentProfile->level }}L)</span></p>
                    @elseif ($user->role === \App\Enums\UserRole::Lecturer)
                        <p>Teaching assignments appear under their lecturer workspace; this panel changes only identity-level attributes.</p>
                    @endif
                    <p>Email verified: <span class="font-medium">{{ $user->email_verified_at ? 'Yes' : 'No' }}</span></p>
                </dl>
            </section>

            <section class="panel px-5 py-5">
                <h2 class="text-sm font-semibold">Audit trail for this account</h2>
                <ol class="mt-3 space-y-2.5 text-xs leading-relaxed">
                    @forelse (\App\Models\AuditLog::where('subject_type', User::class)->where('subject_id', $user->id)->latest('id')->take(8)->get() as $entry)
                        <li>
                            <p class="font-medium">{{ str($entry->action)->replace('_', ' ') }}</p>
                            <p class="text-ink-faint">{{ $entry->created_at->format('j M Y, g:i a') }}@if($entry->actor) · by {{ $entry->actor->name }}@endif</p>
                        </li>
                    @empty
                        <li class="text-ink-faint">Changes to this account will be logged here.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</x-layout.portal>
