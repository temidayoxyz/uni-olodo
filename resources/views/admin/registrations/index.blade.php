<x-layout.portal title="Registration approvals">
    <x-ui.page-header
        title="Course registration approvals"
        subtitle="Submitted baskets awaiting a registry decision. Approving unlocks each student's timetable and course access." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    @if ($pending->isEmpty())
        <x-ui.empty-state icon="inbox-check" title="No registrations waiting">
            When students submit their course baskets, they appear here for approval.
        </x-ui.empty-state>
    @else
        <div class="space-y-4">
            @foreach ($pending as $registration)
                <article class="panel px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ $registration->student->name }}</p>
                            <p class="mt-0.5 text-xs text-ink-faint tabular-nums">
                                {{ $registration->student->studentProfile?->matric_number }} ·
                                {{ $registration->student->studentProfile?->programme?->name }} ({{ $registration->student->studentProfile?->level }}L) ·
                                submitted {{ $registration->submitted_at?->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php $credits = $registration->items->where('status', 'registered')->sum(fn ($i) => $i->offering->course->credit_units); @endphp
                            <span class="badge-neutral tabular-nums">{{ $credits }} credits</span>
                            <form method="POST" action="{{ route('admin.registrations.approve', $registration) }}">
                                @csrf
                                <button type="submit" class="btn-primary btn-sm">Approve</button>
                            </form>
                            <details class="relative">
                                <summary class="btn-secondary btn-sm cursor-pointer list-none [&::-webkit-details-marker]:hidden">Reject…</summary>
                                <form method="POST" action="{{ route('admin.registrations.reject', $registration) }}"
                                      class="panel absolute end-0 z-20 mt-2 w-72 space-y-3 p-4 shadow-lg">
                                    @csrf
                                    <x-ui.textarea label="Reason shown to the student" name="note" rows="3" required />
                                    <button type="submit" class="btn-danger w-full btn-sm">Reject registration</button>
                                </form>
                            </details>
                        </div>
                    </div>

                    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 border-t border-line-soft pt-2.5 text-xs text-ink-soft">
                        @foreach ($registration->items->where('status', 'registered') as $item)
                            <li class="tabular-nums">{{ $item->offering->course->code }} · {{ $item->offering->course->title }}</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        <x-ui.pagination :paginator="$pending" />
    @endif
</x-layout.portal>
