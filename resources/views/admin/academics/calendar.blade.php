<x-layout.portal title="Calendar & windows">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('admin.academics') }}" class="hover:text-pine">Academic structure</a>
        <span aria-hidden="true"> / </span>
        <span>Calendar</span>
    </nav>

    <x-ui.page-header title="Academic calendar & registration windows"
        subtitle="The current semester drives dashboards, the public site, and whether students can register. Windows are checked server-side on every submission." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif

    <div class="space-y-10">
        @foreach ($sessions as $session)
            <section aria-labelledby="cal-{{ $session->id }}">
                <h2 class="flex items-baseline gap-2 font-display text-lg font-semibold tracking-tight">
                    {{ $session->name }}
                    @if ($session->is_current)<span class="badge-pine">Current session</span>@endif
                    <span class="ms-auto text-xs font-normal text-ink-faint tabular-nums">{{ $session->starts_on?->format('M Y') }} – {{ $session->ends_on?->format('M Y') }}</span>
                </h2>

                <div class="mt-3 grid gap-4 lg:grid-cols-2">
                    @foreach ($session->semesters as $semester)
                        <div class="panel px-5 py-4">
                            <h3 class="flex items-center justify-between gap-2 text-sm font-semibold">
                                {{ $semester->name }}
                                @if ($semester->is_current)<span class="badge-info badge">Live</span>@endif
                            </h3>
                            <p class="mt-1 text-xs tabular-nums text-ink-faint">
                                Teaching: {{ $semester->starts_on?->format('j M Y') }} – {{ $semester->ends_on?->format('j M Y') }}
                            </p>

                            <form method="POST" action="{{ route('admin.semesters.window', $semester) }}" class="mt-3 flex flex-wrap items-end gap-3 border-t border-line-soft pt-3">
                                @csrf
                                @method('PUT')
                                <label class="text-xs">
                                    <span class="mb-1 block font-semibold text-ink">Registration opens</span>
                                    <input type="datetime-local" name="registration_opens_at" value="{{ $semester->registration_opens_at?->format('Y-m-d\TH:i') }}"
                                           class="input w-52 !py-1.5 text-xs tabular-nums">
                                </label>
                                <label class="text-xs">
                                    <span class="mb-1 block font-semibold text-ink">Closes</span>
                                    <input type="datetime-local" name="registration_closes_at" value="{{ $semester->registration_closes_at?->format('Y-m-d\TH:i') }}"
                                           class="input w-52 !py-1.5 text-xs tabular-nums">
                                </label>
                                <button type="submit" class="btn-secondary btn-sm">Save window</button>
                            </form>

                            <p class="mt-2 text-xs {{ $semester->registrationIsOpen() ? 'font-semibold text-success' : 'text-ink-faint' }}">
                                Status now: {{ $semester->registrationIsOpen() ? 'OPEN for registration' : 'closed' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-layout.portal>
