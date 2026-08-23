<x-layout.portal title="Review {{ $application->number }}">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('admin.admissions.index') }}" class="hover:text-pine">Admissions</a>
        <span aria-hidden="true"> / </span>
        <span class="tabular-nums">{{ $application->number }}</span>
    </nav>

    <x-ui.page-header
        :title="$application->first_name.' '.$application->other_names.' '.$application->last_name"
        :subtitle="$application->number.' · submitted '.$application->submitted_at?->format('j F Y, g:i a').' for '.$application->intakeSession?->name" />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="space-y-6">
            {{-- Applicant details --}}
            <article class="panel">
                <header class="panel-header"><h2 class="text-sm font-semibold">Applicant details</h2></header>
                <dl class="grid gap-x-8 gap-y-4 px-5 py-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-faint">Date of birth</dt><dd class="font-medium tabular-nums">{{ $application->date_of_birth->format('j F Y') }} <span class="text-xs text-ink-faint">({{ $application->date_of_birth->age }} yrs)</span></dd></div>
                    <div><dt class="text-ink-faint">Gender</dt><dd class="font-medium capitalize">{{ $application->gender }}</dd></div>
                    <div><dt class="text-ink-faint">Phone</dt><dd class="font-medium tabular-nums">{{ $application->phone }}</dd></div>
                    <div><dt class="text-ink-faint">State of origin</dt><dd class="font-medium">{{ $application->state_of_origin }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-ink-faint">Address</dt><dd>{{ $application->address }}</dd></div>
                </dl>
            </article>

            <article class="panel">
                <header class="panel-header"><h2 class="text-sm font-semibold">Educational background</h2></header>
                <dl class="grid gap-x-8 gap-y-4 px-5 py-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-faint">Qualification</dt><dd class="font-medium uppercase">{{ strtoupper($application->qualification) }} {{ (int) $application->examination_year }}</dd></div>
                    <div><dt class="text-ink-faint">Previous school</dt><dd class="font-medium">{{ $application->previous_school ?? '—' }}</dd></div>
                    @if ($application->personal_statement)
                        <div class="sm:col-span-2"><dt class="text-ink-faint">Personal statement</dt><dd class="leading-relaxed">{{ $application->personal_statement }}</dd></div>
                    @endif
                </dl>
            </article>

            {{-- Programme choices --}}
            <article class="panel">
                <header class="panel-header"><h2 class="text-sm font-semibold">Programme choices</h2></header>
                <ol class="divide-y divide-line-soft">
                    @foreach ($application->choices as $choice)
                        <li class="flex items-center justify-between px-5 py-3.5 text-sm">
                            <div>
                                <p class="font-medium">{{ $choice->programme->name }}</p>
                                <p class="text-xs text-ink-faint">{{ $choice->programme->department->faculty->code }} · {{ $choice->programme->department->name }}</p>
                            </div>
                            <span class="badge-neutral">{{ ['First', 'Second'][$choice->rank - 1] }} choice</span>
                        </li>
                    @endforeach
                </ol>
            </article>

            {{-- Documents with verification controls --}}
            <article class="panel">
                <header class="panel-header"><h2 class="text-sm font-semibold">Documents</h2></header>
                <ul class="divide-y divide-line-soft">
                    @forelse ($application->documents as $document)
                        <li class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">{{ $document->typeLabel() }}</p>
                                    <p class="mt-0.5 truncate text-xs text-ink-faint">
                                        {{ $document->original_name }} · {{ number_format($document->size_bytes / 1024) }} KB
                                        @if ($document->reviewed_at) · reviewed {{ $document->reviewed_at->diffForHumans() }} by {{ $document->reviewer?->name }}@endif
                                    </p>
                                    @if ($document->reviewer_note)
                                        <p class="mt-1 text-xs font-medium text-danger">{{ $document->reviewer_note }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <span class="badge {{ match ($document->verification->value) { 'verified' => 'badge-success', 'rejected' => 'badge-danger', default => 'badge-warning' } }}">{{ $document->verification->label() }}</span>
                                    <a href="{{ route('admin.admissions.document', $document) }}" class="btn-secondary btn-sm">Open file</a>
                                    @if ($document->verification->value === 'pending')
                                        <form method="POST" action="{{ route('admin.admissions.document.verify', $document) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary btn-sm">Verify</button>
                                        </form>
                                        <details class="relative">
                                            <summary class="btn-danger btn-sm cursor-pointer list-none [&::-webkit-details-marker]:hidden">Reject…</summary>
                                            <form method="POST" action="{{ route('admin.admissions.document.reject', $document) }}"
                                                  class="panel absolute end-0 z-20 mt-2 w-72 space-y-3 p-4 shadow-lg">
                                                @csrf
                                                <x-ui.textarea label="What must the applicant fix?" name="note" rows="3" required />
                                                <button type="submit" class="btn-danger w-full btn-sm">Reject document</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-ink-soft">No documents have been uploaded.</li>
                    @endforelse
                </ul>
            </article>
        </div>

        {{-- Decision panel --}}
        <aside class="space-y-6">
            <section class="panel px-5 py-5" aria-labelledby="decision-heading">
                <h2 id="decision-heading" class="text-sm font-semibold">Status & decision</h2>

                <p class="mt-3 flex items-center gap-2 text-sm">
                    @php
                        $badge = match ($application->status->value) {
                            'draft', 'withdrawn' => 'badge-neutral',
                            'submitted', 'under_review' => 'badge-info',
                            'more_info_required' => 'badge-warning',
                            'accepted', 'conditionally_accepted', 'enrolled' => 'badge-success',
                            default => 'badge-danger',
                        };
                    @endphp
                    <span class="{{ $badge }}">{{ $application->status->label() }}</span>
                    @if ($application->decision_at)
                        <span class="text-xs text-ink-faint">{{ $application->decision_at->diffForHumans() }}</span>
                    @endif
                </p>

                @if ($application->decision_note)
                    <p class="mt-3 rounded-[var(--radius-control)] bg-surface-dim px-3.5 py-2.5 text-xs leading-relaxed text-ink-soft">
                        “{{ $application->decision_note }}”
                        @if ($application->decider)<span class="block mt-1 not-italic">— {{ $application->decider->name }}</span>@endif
                    </p>
                @endif

                @if ($application->statusIs(ApplicationStatus::Submitted))
                    <form method="POST" action="{{ route('admin.admissions.review', $application) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn-primary w-full">Start review</button>
                    </form>
                @elseif ($application->statusIs(ApplicationStatus::UnderReview, ApplicationStatus::MoreInfoRequired))
                    <form method="POST" action="{{ route('admin.admissions.decide', $application) }}" class="mt-4 space-y-4 border-t border-line-soft pt-4">
                        @csrf
                        <x-ui.select label="Decision" name="decision" required :options="[
                            'under_review' => 'Return to review',
                            'more_info_required' => 'Request more information',
                            'conditionally_accepted' => 'Offer — conditional admission',
                            'accepted' => 'Offer — admission',
                            'waitlisted' => 'Waitlist',
                            'rejected' => 'Not admitted',
                        ]" :selected="$application->statusIs(ApplicationStatus::MoreInfoRequired) ? 'more_info_required' : ''" placeholder="Choose action…" />
                        <x-ui.textarea label="Note to applicant" name="note" rows="4"
                                      hint="Required for decisions and information requests. Shown on the applicant's dashboard." />
                        <button type="submit" class="btn-primary w-full">Record decision</button>
                    </form>
                @else
                    @php
                        $stateNote = match ($application->status->value) {
                            'accepted', 'conditionally_accepted' => 'The offer is open; the applicant has not responded yet.',
                            'enrolled' => 'The applicant accepted the offer and is now a student.',
                            'rejected' => 'A final decision has been recorded.',
                            default => 'No further action is available in this state.',
                        };
                    @endphp
                    <p class="mt-4 text-xs leading-relaxed text-ink-faint">{{ $stateNote }}</p>
                    @if ($application->statusIs(ApplicationStatus::Accepted, ApplicationStatus::ConditionallyAccepted))
                        <a href="/student" class="btn-secondary btn-sm mt-3 w-full pointer-events-none opacity-50" tabindex="-1" aria-disabled="true">Awaiting applicant response</a>
                    @endif
                @endif
            </section>

            {{-- Audit trail for this application --}}
            <section class="panel px-5 py-5">
                <h2 class="text-sm font-semibold">History</h2>
                <ol class="mt-3 space-y-3 text-xs leading-relaxed">
                    @foreach (\App\Models\AuditLog::where('subject_type', Application::class)->where('subject_id', $application->id)->latest('id')->take(10)->get() as $entry)
                        <li class="flex gap-2.5">
                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-pine-line"></span>
                            <div>
                                <p class="font-medium text-ink">{{ str($entry->action)->after('application.')->replace('_', ' ') }}</p>
                                <p class="text-ink-faint">{{ $entry->created_at->format('j M Y, g:i a') }}@if($entry->actor) · {{ $entry->actor->name }}@endif</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-ink-faint">Actions on this application will be logged here.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</x-layout.portal>
