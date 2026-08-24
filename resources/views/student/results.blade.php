<x-layout.portal title="Results">
    <x-ui.page-header title="Official results"
        subtitle="Only results approved by the registry appear here. Current-semester coursework remains provisional until published." />

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="panel px-5 py-4">
            <p class="text-xs text-ink-faint uppercase tracking-wide">Cumulative GPA</p>
            <p class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $cgpa !== null ? number_format($cgpa, 2) : '—' }}</p>
            <p class="text-xs text-ink-faint">5-point scale</p>
        </div>
        <div class="panel px-5 py-4">
            <p class="text-xs text-ink-faint uppercase tracking-wide">Credits earned</p>
            <p class="mt-1 font-display text-3xl font-semibold tabular-nums">{{ $creditsEarned }}</p>
            <p class="text-xs text-ink-faint">passed courses</p>
        </div>
        <a href="{{ route('student.transcript') }}" class="btn-secondary ms-auto"><x-lucide-file-text class="size-4" /> Unofficial transcript</a>
    </div>

    @if ($hasProvisionalNote)
        <div class="mb-6">
            <x-ui.alert type="info">
                Results for {{ $currentSemester->session->name }} {{ $currentSemester->name }} are still provisional —
                your lecturer's score sheet is not yet official. Published grades will appear below once the registry releases them.
            </x-ui.alert>
        </div>
    @endif

    @forelse ($grouped as $sessionName => $semesters)
        <section aria-labelledby="session-{{ \Illuminate\Support\Str::slug($sessionName) }}" class="mb-10">
            @foreach ($semesters as $entry)
                <h2 class="font-display text-xl font-semibold tracking-tight mb-3">
                    {{ $entry['semester']->session->name }} · {{ $entry['semester']->name }}
                </h2>
                <div class="table-wrap mb-6 !rounded-b-none">
                    <table class="table min-w-[40rem]">
                        <thead>
                            <tr>
                                <th scope="col">Course</th>
                                <th scope="col" class="text-center">Units</th>
                                <th scope="col" class="text-center">CA (40)</th>
                                <th scope="col" class="text-center">Exam (60)</th>
                                <th scope="col" class="text-center">Total</th>
                                <th scope="col" class="text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entry['rows'] as $row)
                                <tr>
                                    <td><span class="font-medium tabular-nums">{{ $row->offering->course->code }}</span> — {{ $row->offering->course->title }}</td>
                                    <td class="num">{{ $row->offering->course->credit_units }}</td>
                                    <td class="num">{{ number_format($row->ca_score, 1) }}</td>
                                    <td class="num">{{ number_format($row->exam_score, 1) }}</td>
                                    <td class="num font-semibold">{{ number_format($row->total, 1) }}</td>
                                    <td class="text-center">
                                        <span class="{{ $row->is_passed ? 'badge-success' : 'badge-danger' }}">{{ $row->grade_letter }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="-mt-3 mb-4 rounded-b-[var(--radius-surface)] border border-t-0 border-line bg-paper-deep px-4 py-2.5 text-sm">
                    Semester GPA:
                    <strong class="tabular-nums">{{ $entry['gpa'] !== null ? number_format($entry['gpa'], 2) : '—' }}</strong>
                    <span class="text-ink-faint">· weighted over {{ $entry['rows']->sum(fn ($r) => $r->offering->course->credit_units) }} units</span>
                </p>
            @endforeach
        </section>
    @empty
        <x-ui.empty-state icon="award" title="No official results yet"
            actionUrl="/student/academics" actionLabel="See my academics">
            When the registry publishes your semester results, they will appear here with
            full detail and count toward your cumulative GPA.
        </x-ui.empty-state>
    @endforelse
</x-layout.portal>
