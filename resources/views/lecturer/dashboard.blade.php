<x-layout.portal title="Teaching dashboard">
    @php $user = auth()->user(); @endphp
    @php
        $daypart = now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening');
        $surname = collect(explode(' ', str_replace(['Dr.', 'Mr.', 'Mrs.', 'Engr.'], '', $user->name)))->last();
    @endphp
    <x-ui.page-header
        :title="'Good '.$daypart.', '.$surname"
        :subtitle="$semester ? 'Currently teaching · '.$semester->session->name.' '.$semester->name : null" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- My offerings --}}
            <section aria-labelledby="offerings-heading" class="panel">
                <div class="panel-header">
                    <h2 id="offerings-heading" class="text-sm font-semibold">My courses this semester</h2>
                    <a href="/courses" class="text-xs font-medium text-pine hover:underline">Manage all →</a>
                </div>

                @if ($offerings->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-ink-soft">
                        You have no course offerings assigned for {{ $semester?->session->name ?? 'this semester' }}.
                        Contact the examinations unit if this looks wrong.
                    </p>
                @else
                    <ul class="divide-y divide-line-soft">
                        @foreach ($offerings as $offering)
                            <li class="flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4">
                                <div class="min-w-0 flex-1 basis-48">
                                    <p class="truncate text-sm font-semibold">{{ $offering->course->code }} — {{ $offering->course->title }}</p>
                                    <p class="mt-0.5 text-xs text-ink-faint tabular-nums">
                                        {{ $offering->enrolled }} students enrolled
                                        @if ($offering->capacity) · capacity {{ $offering->capacity }}@endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($offering->ungraded > 0)
                                        <a href="/courses/{{ $offering->id }}/assignments" class="badge-warning badge hover:brightness-95">
                                            <span class="dot"></span> {{ $offering->ungraded }} to grade
                                        </a>
                                    @else
                                        <span class="badge-neutral">Grading up to date</span>
                                    @endif
                                    <a href="/courses/{{ $offering->id }}" class="btn-secondary btn-sm">Open</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Today's teaching --}}
            <section aria-labelledby="today-heading" class="panel">
                <div class="panel-header">
                    <h2 id="today-heading" class="text-sm font-semibold">Today's teaching</h2>
                    <span class="text-xs text-ink-faint">{{ today()->translatedFormat('l j F') }}</span>
                </div>
                @if ($todaySlots->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-ink-soft">No classes scheduled today.</p>
                @else
                    <ul class="divide-y divide-line-soft">
                        @foreach ($todaySlots as $slot)
                            <li class="flex items-center gap-4 px-5 py-3.5">
                                <p class="w-16 shrink-0 text-sm font-semibold tabular-nums">
                                    {{ \Carbon\Carbon::parse($slot->schedule->starts_at)->format('g:i') }}
                                </p>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $slot->offering->course->code }} — {{ $slot->schedule->venue ?? 'Venue TBA' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Side --}}
        <div class="space-y-6">
            <section class="panel px-5 py-5">
                <h2 class="text-sm font-semibold">Quick actions</h2>
                <div class="mt-4 grid gap-2">
                    <a href="/courses" class="btn-secondary justify-start"><x-lucide-upload class="size-4" /> Publish a lesson or material</a>
                    <a href="/courses?queue=grading" class="btn-secondary justify-start"><x-lucide-check-square class="size-4" /> Grade submissions</a>
                    <a href="{{ route('lecturer.results') }}" class="btn-secondary justify-start"><x-lucide-award class="size-4" /> Submit provisional results</a>
                </div>
            </section>
        </div>
    </div>
</x-layout.portal>
