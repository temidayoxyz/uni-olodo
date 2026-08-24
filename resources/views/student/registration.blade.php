<x-layout.portal title="Course registration">
    <x-ui.page-header
        :title="'Register courses — '.($semester?->session->name.' '.$semester?->name ?? 'No active semester')"
        :subtitle="$profile ? $profile->programme->name.' · '.$profile->level.' level' : null" />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger" title="Cannot do that yet">{{ session('error') }}</x-ui.alert></div>@endif

    @unless ($windowOpen)
        <div class="mb-6">
            <x-ui.alert type="warning" title="Registration is closed">
                The window for {{ $semester?->name }} ran from
                {{ $semester?->registration_opens_at?->format('j F') }} to {{ $semester?->registration_closes_at?->format('j F Y') }}.
                Late registration requires your dean's approval — contact your academic adviser if you still need to register.
            </x-ui.alert>
        </div>
    @endunless

    {{-- Status banner when already submitted/approved --}}
    @if ($registration && ! $registration->statusIs(\App\Enums\RegistrationStatus::Draft, \App\Enums\RegistrationStatus::Rejected))
        <div class="mb-6">
            <x-ui.alert type="{{ $registration->statusIs(\App\Enums\RegistrationStatus::Approved) ? 'success' : 'info' }}"
                        title="Registration {{ $registration->status->label() }}">
                @if ($registration->statusIs(\App\Enums\RegistrationStatus::Approved))
                    Approved {{ $registration->approved_at?->diffForHumans() }} — {{ $credits }} credits across {{ $basket->count() }} courses. Your timetable is live.
                    <a href="/student/timetable" class="ms-1 font-semibold underline underline-offset-2">View timetable</a>
                @else
                    Submitted {{ $registration->submitted_at?->diffForHumans() }} and awaiting registry approval. You'll be notified of the outcome.
                @endif
            </x-ui.alert>
        </div>
    @elseif ($registration && $registration->statusIs(\App\Enums\RegistrationStatus::Rejected))
        <div class="mb-6">
            <x-ui.alert type="danger" title="Previous submission was not approved">
                {{ $registration->note ?? 'See the registry for details.' }} You can edit your basket below and resubmit.
            </x-ui.alert>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        {{-- Available offerings --}}
        <section aria-labelledby="available-heading" class="panel">
            <div class="panel-header">
                <h2 id="available-heading" class="text-sm font-semibold">Courses open to you</h2>
                <span class="text-xs text-ink-faint tabular-nums">{{ $available->count() }} available</span>
            </div>

            @if ($available->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink-soft">No further open offerings at your level this semester.</p>
            @else
                <ul class="divide-y divide-line-soft">
                    @foreach ($available as $offering)
                        @php $violations = $checks[$offering->id] ?? []; @endphp
                        <li class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
                                <div class="min-w-0 flex-1 basis-56">
                                    <p class="text-sm font-semibold">{{ $offering->course->code }} — {{ $offering->course->title }}</p>
                                    <p class="mt-0.5 text-xs text-ink-faint">
                                        {{ $offering->course->credit_units }} credits · {{ $offering->lecturer?->name ?? 'Staff TBA' }}
                                        @foreach ($offering->schedules as $slot)
                                            · {{ $slot->weekdayName() }} {{ \Carbon\Carbon::parse($slot->starts_at)->format('g:i') }}
                                        @endforeach
                                    </p>
                                    @if ($offering->course->prerequisites->isNotEmpty())
                                        <p class="mt-1 text-xs text-ink-faint">
                                            Requires: {{ $offering->course->prerequisites->pluck('code')->implode(', ') }}
                                        </p>
                                    @endif
                                    @if ($violations !== [])
                                        <ul class="mt-1.5 space-y-0.5">
                                            @foreach ($violations as $violation)
                                                <li class="text-xs font-medium text-danger">{{ $violation }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('student.registration.add') }}" class="shrink-0 self-center">
                                    @csrf
                                    <input type="hidden" name="offering" value="{{ $offering->id }}">
                                    <button type="submit" class="btn-secondary btn-sm" @disabled($violations !== [] || ! $windowOpen)>Add</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Basket --}}
        <aside aria-labelledby="basket-heading" class="space-y-6 lg:sticky lg:top-20 lg:self-start">
            <section class="panel px-5 py-5">
                <h2 id="basket-heading" class="text-sm font-semibold">My basket</h2>

                @if ($basket->isEmpty())
                    <p class="mt-3 text-sm leading-relaxed text-ink-soft">
                        Nothing added yet. Courses you add appear here with a running credit total.
                    </p>
                @else
                    <ul class="mt-4 divide-y divide-line-soft border-y border-line-soft">
                        @foreach ($basket as $offering)
                            @php $item = $basketItems[$offering->id] ?? null; @endphp
                            <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                <span class="min-w-0 truncate font-medium">{{ $offering->course->code }}</span>
                                <span class="tabular-nums text-ink-soft">{{ $offering->course->credit_units }}u</span>
                                @if ($item && $registration?->statusIs(\App\Enums\RegistrationStatus::Draft, \App\Enums\RegistrationStatus::Rejected) && $windowOpen)
                                    <form method="POST" action="{{ route('student.registration.remove', $item) }}">
                                        @csrf
                                        <button type="submit" class="btn-ghost btn-sm" aria-label="Remove {{ $offering->course->code }}">
                                            <x-lucide-x class="size-3.5" />
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-soft">Total credits</dt>
                            <dd class="font-semibold tabular-nums {{ $credits > $maxCredits ? 'text-danger' : '' }}">{{ $credits }} / {{ $maxCredits }}</dd></div>
                    </dl>
                @endif

                @if ($windowOpen && $registration?->statusIs(\App\Enums\RegistrationStatus::Draft, \App\Enums\RegistrationStatus::Rejected))
                    <form method="POST" action="{{ route('student.registration.submit') }}" class="mt-5 border-t border-line-soft pt-4">
                        @csrf
                        <button type="submit" class="btn-primary w-full" @disabled($credits === 0)>Submit for approval</button>
                        <p class="mt-2 text-center text-xs text-ink-faint">Submitting locks your basket until the registry decides.</p>
                    </form>
                @elseif (! $windowOpen && ($registration === null || $registration->activeItems()->count() === 0))
                    <a href="/support" class="btn-secondary btn-sm mt-5 w-full">Contact the registry</a>
                @endif
            </section>

            <section class="panel px-5 py-5 text-sm leading-relaxed">
                <h2 class="text-sm font-semibold">How approval works</h2>
                <p class="mt-2 text-ink-soft">
                    Submitting sends your basket to the registry, which checks it against
                    your programme requirements before approving. You keep seeing your
                    basket here while it waits; teaching access opens on approval.
                </p>
            </section>
        </aside>
    </div>
</x-layout.portal>
