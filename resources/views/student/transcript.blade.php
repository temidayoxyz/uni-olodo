<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unofficial transcript — {{ $user->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-paper">
    <div class="mx-auto max-w-3xl bg-white px-10 py-10 print:px-0">
        <header class="flex items-center justify-between gap-6 border-b-2 border-[var(--color-pine)] pb-5">
            <div class="flex items-center gap-3 text-pine">
                <x-brand-mark class="size-12" />
                <div>
                    <p class="font-display text-xl leading-tight font-semibold">University of Olodo</p>
                    <p class="text-xs text-ink-faint">Olado, Ibadan, Oyo State · olodo.edu.ng</p>
                </div>
            </div>
            <div class="text-end">
                <p class="text-xs font-bold tracking-widest uppercase">Unofficial transcript</p>
                <p class="mt-0.5 text-xs text-ink-faint tabular-nums">Generated {{ $generatedAt->format('j F Y') }}</p>
            </div>
        </header>

        <dl class="mt-5 grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-ink-faint">Name</dt><dd class="font-semibold">{{ $user->name }}</dd></div>
            <div><dt class="text-xs text-ink-faint">Matric number</dt><dd class="font-semibold tabular-nums">{{ $profile?->matric_number }}</dd></div>
            <div><dt class="text-xs text-ink-faint">Programme</dt><dd class="font-semibold">{{ $profile?->programme?->name }}</dd></div>
            <div><dt class="text-xs text-ink-faint">Level</dt><dd class="font-semibold tabular-nums">{{ $profile?->level }}</dd></div>
        </dl>

        <p class="mt-4 rounded border border-warning-line bg-warning-tint px-3 py-2 text-xs leading-relaxed">
            <strong>Unofficial document.</strong> Printed from the student portal for personal reference.
            Official transcripts are issued by the registry on request and bear the university seal.
        </p>

        @forelse ($results->groupBy(fn ($r) => $r->semester->session->name) as $sessionName => $sessionResults)
            <h2 class="mt-7 mb-2 font-display text-lg font-semibold">{{ $sessionName }} Session</h2>
            @foreach ($sessionResults->groupBy(fn ($r) => $r->semester->number) as $semesterRows)
                @php
                    $first = $semesterRows->first();
                    $gpa = \App\Support\GradeScale::gpa($semesterRows->map(fn ($r) => [
                        'total' => (float) $r->total, 'credit_units' => $r->offering->course->credit_units,
                    ]));
                @endphp
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-y border-line bg-surface-dim">
                            <th class="py-1.5 text-start font-semibold" colspan="2">{{ $first->semester->name }}</th>
                            <th class="py-1.5 text-center font-semibold">Units</th>
                            <th class="py-1.5 text-center font-semibold">Total</th>
                            <th class="py-1.5 text-center font-semibold">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($semesterRows as $row)
                            <tr class="border-b border-line-soft">
                                <td class="py-1.5 font-medium tabular-nums">{{ $row->offering->course->code }}</td>
                                <td class="py-1.5">{{ $row->offering->course->title }}</td>
                                <td class="py-1.5 text-center tabular-nums">{{ $row->offering->course->credit_units }}</td>
                                <td class="py-1.5 text-center tabular-nums">{{ number_format($row->total, 1) }}</td>
                                <td class="py-1.5 text-center font-semibold">{{ $row->grade_letter }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-b border-line">
                            <td colspan="4" class="py-1.5 text-end text-xs uppercase tracking-wide text-ink-faint">Semester GPA</td>
                            <td class="py-1.5 text-center font-bold tabular-nums">{{ $gpa !== null ? number_format($gpa, 2) : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @empty
            <p class="mt-8 text-sm text-ink-soft">No published results on record yet.</p>
        @endforelse

        @if ($results->isNotEmpty())
            <footer class="mt-8 flex items-center justify-between border-t-2 border-pine pt-4">
                <p class="text-sm">Cumulative GPA:
                    <strong class="font-display text-xl tabular-nums">{{ \App\Support\GradeScale::gpa($results->map(fn ($r) => ['total' => (float) $r->total, 'credit_units' => $r->offering->course->credit_units])) ? number_format(\App\Support\GradeScale::gpa($results->map(fn ($r) => ['total' => (float) $r->total, 'credit_units' => $r->offering->course->credit_units])), 2) : '—' }}</strong>
                    <span class="text-xs text-ink-faint">(5-point scale)</span></p>
                <p class="text-xs text-ink-faint">{{ $results->count() }} official course records</p>
            </footer>
        @endif
    </div>

    <div class="no-print mx-auto mt-6 max-w-3xl flex items-center justify-between px-2 pb-10">
        <a href="{{ route('student.results') }}" class="btn-secondary">← Back to results</a>
        <button onclick="window.print()" class="btn-primary"><x-lucide-printer class="size-4" /> Print this page</button>
    </div>
</body>
</html>
