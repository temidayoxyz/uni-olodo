<x-layout.portal title="Application {{ $application->number }}">
    <x-ui.page-header
        title="Undergraduate application"
        :subtitle="$application->number.' · save your progress at any point — nothing is final until you review and submit.'" />

    @if (session('status'))
        <div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>
    @endif
    @if (session('error'))
        <div class="mb-5"><x-ui.alert type="danger" title="Not submitted yet">{{ session('error') }}</x-ui.alert></div>
    @endif

    {{-- Step navigation --}}
    @php
        $steps = [
            'personal' => 'Personal details',
            'education' => 'Education',
            'choices' => 'Programme choices',
            'documents' => 'Documents',
            'review' => 'Review & submit',
        ];
        $currentIdx = array_search($step, $steps, true);
    @endphp
    <nav aria-label="Application steps">
        <ol class="mb-6 flex flex-wrap gap-x-1 gap-y-2 text-sm">
            @foreach ($steps as $key => $label)
                <li class="flex items-center gap-1">
                    @if ($key !== 'personal')<span class="mx-1 text-line" aria-hidden="true">/</span>@endif
                    <a href="{{ route('applicant.application', ['step' => $key]) }}"
                       @if ($key === $step) aria-current="step" @endif
                       class="rounded-[var(--radius-control)] px-2.5 py-1 font-medium transition-colors
                           {{ $key === $step ? 'bg-pine text-white' : ($loop->index < $currentIdx ? 'text-pine hover:bg-pine-tint' : 'text-ink-faint hover:bg-paper-deep') }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div>
            @if ($step === 'personal')
                <form method="POST" action="{{ route('applicant.application.personal') }}" class="panel space-y-5 p-6">
                    @csrf
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input label="First name" name="first_name" required :value="$application->first_name" />
                        <x-ui.input label="Last name" name="last_name" required :value="$application->last_name" />
                    </div>
                    <x-ui.input label="Other names (optional)" name="other_names" :value="$application->other_names" />
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-ui.input label="Date of birth" name="date_of_birth" type="date" required :value="$application->date_of_birth?->toDateString()" />
                        <x-ui.select label="Gender" name="gender" required :options="['male' => 'Male', 'female' => 'Female']" :selected="$application->gender" placeholder="Select…" />
                        <x-ui.input label="Phone" name="phone" required :value="$application->phone" placeholder="08031234567" hint="Nigerian format, 11 digits." />
                    </div>
                    <x-ui.textarea label="Contact address" name="address" required :value="$application->address" rows="3" />
                    <x-ui.select label="State of origin" name="state_of_origin" required :options="collect(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'])->combine(collect(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara']))->all()" :selected="$application->state_of_origin" placeholder="Select state…" />
                    <div class="flex justify-end border-t border-line-soft pt-4">
                        <button type="submit" class="btn-primary">Save & continue</button>
                    </div>
                </form>

            @elseif ($step === 'education')
                <form method="POST" action="{{ route('applicant.application.education') }}" class="panel space-y-5 p-6">
                    @csrf
                    <x-ui.select label="Qualification held" name="qualification" required :options="[
                        'waec' => 'WAEC (SSCE)',
                        'neco' => 'NECO (SSCE)',
                        'nabteb' => 'NABTEB',
                        'equivalent' => 'Other recognised equivalent',
                    ]" :selected="$application->qualification" placeholder="Select qualification…" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.select label="Examination year" name="examination_year" required :options="collect(range((int) date('Y'), 2005))->combine(collect(range((int) date('Y'), 2005)))->all()" :selected="(string) $application->examination_year" placeholder="Select year…" />
                        <x-ui.input label="Previous school" name="previous_school" :value="$application->previous_school" placeholder="e.g. Government College, Ibadan" />
                    </div>
                    <x-ui.textarea label="Personal statement (optional)" name="personal_statement" :value="$application->personal_statement" rows="5"
                                  hint="Up to 3000 characters. Tell us why Olodo and this programme." />
                    <div class="flex justify-end border-t border-line-soft pt-4">
                        <button type="submit" class="btn-primary">Save & continue</button>
                    </div>
                </form>

            @elseif ($step === 'choices')
                <form method="POST" action="{{ route('applicant.application.choices') }}" class="panel space-y-5 p-6">
                    @csrf
                    <p class="text-sm leading-relaxed text-ink-soft">
                        You may choose up to two programmes. If your first choice is not offered,
                        your second is considered automatically — there is no extra fee.
                    </p>

                    @php $choices = $application->choices->keyBy('rank'); @endphp
                    <x-ui.select label="First choice" name="choice_1" required
                                :options="$programmes->mapWithKeys(fn ($p) => [$p->id => $p->name])"
                                :selected="(string) ($choices[1]->programme_id ?? '')" placeholder="Choose a programme…" />
                    <x-ui.select label="Second choice (optional)" name="choice_2"
                                :options="$programmes->mapWithKeys(fn ($p) => [$p->id => $p->name])"
                                :selected="(string) ($choices[2]->programme_id ?? '')" placeholder="Choose a programme…" />

                    <div class="flex justify-end border-t border-line-soft pt-4">
                        <button type="submit" class="btn-primary">Save & continue</button>
                    </div>
                </form>

            @elseif ($step === 'documents')
                <section class="panel p-6" aria-labelledby="docs-heading">
                    <h2 id="docs-heading" class="font-display text-lg font-semibold">Required documents</h2>
                    <p class="mt-1 text-sm text-ink-soft">PDF or image (jpg/png), up to 4 MB per file. Uploads are checked by the admissions office.</p>

                    <ul class="mt-6 divide-y divide-line-soft border-y border-line-soft">
                        @foreach (['passport_photograph', 'olevel_result', 'birth_certificate'] as $required)
                            @php $doc = $application->documents->firstWhere('type', $required); @endphp
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">{{ $documentTypes[$required] }} <span class="text-danger">*</span></p>
                                    @if ($doc)
                                        <p class="mt-0.5 truncate text-xs text-ink-faint">
                                            {{ $doc->original_name }} · {{ number_format($doc->size_bytes / 1024) }} KB · uploaded {{ $doc->created_at->diffForHumans() }}
                                        </p>
                                        @if ($doc->reviewer_note)
                                            <p class="mt-1 text-xs font-medium text-danger">{{ $doc->reviewer_note }}</p>
                                        @endif
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($doc)
                                        <span class="badge {{ match ($doc->verification->value) { 'verified' => 'badge-success', 'rejected' => 'badge-danger', default => 'badge-warning' } }}">{{ $doc->verification->label() }}</span>
                                        <a href="{{ route('applicant.documents.download', $doc) }}" class="btn-secondary btn-sm">View</a>
                                    @endif
                                    <form method="POST" action="{{ route('applicant.documents.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $required }}">
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required
                                               class="block w-52 text-xs file:mr-2 file:cursor-pointer file:rounded-[var(--radius-control)] file:border-0 file:bg-paper-deep file:px-2.5 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-line"
                                               aria-label="Upload {{ $documentTypes[$required] }}">
                                        <button type="submit" class="btn-primary btn-sm">{{ $doc ? 'Replace' : 'Upload' }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach

                        @foreach (['entrance_exam_slip'] as $optional)
                            @php $doc = $application->documents->firstWhere('type', $optional); @endphp
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">{{ $documentTypes[$optional] }} <span class="text-xs font-normal text-ink-faint">(if already issued)</span></p>
                                    @if ($doc)
                                        <p class="mt-0.5 truncate text-xs text-ink-faint">{{ $doc->original_name }} · uploaded {{ $doc->created_at->diffForHumans() }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($doc)
                                        <span class="badge-warning badge">{{ $doc->verification->label() }}</span>
                                        <a href="{{ route('applicant.documents.download', $doc) }}" class="btn-secondary btn-sm">View</a>
                                    @endif
                                    <form method="POST" action="{{ route('applicant.documents.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $optional }}">
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required
                                               class="block w-52 text-xs file:mr-2 file:cursor-pointer file:rounded-[var(--radius-control)] file:border-0 file:bg-paper-deep file:px-2.5 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-line"
                                               aria-label="Upload {{ $documentTypes[$optional] }}">
                                        <button type="submit" class="btn-primary btn-sm">{{ $doc ? 'Replace' : 'Upload' }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 flex justify-end">
                        <a href="{{ route('applicant.application', ['step' => 'review']) }}" class="btn-primary">Continue to review</a>
                    </div>
                </section>

            @elseif ($step === 'review')
                <section class="space-y-6" aria-labelledby="review-heading">
                    <h2 id="review-heading" class="sr-only">Review and submit</h2>

                    @if ($blockers !== [])
                        <x-ui.alert type="warning" title="Complete these before submitting">
                            {{ implode(' · ', $blockers) }}
                        </x-ui.alert>
                    @endif

                    <article class="panel">
                        <header class="panel-header"><h3 class="text-sm font-semibold">Personal details</h3><a href="{{ route('applicant.application', ['step' => 'personal']) }}" class="text-xs font-medium text-pine hover:underline">Edit</a></header>
                        <dl class="grid gap-x-8 gap-y-3 px-5 py-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-ink-faint">Name</dt><dd class="font-medium">{{ $application->first_name }} {{ $application->other_names }} {{ $application->last_name }}</dd></div>
                            <div><dt class="text-ink-faint">Date of birth</dt><dd class="font-medium">{{ $application->date_of_birth?->format('j F Y') ?? '—' }}</dd></div>
                            <div><dt class="text-ink-faint">Gender</dt><dd class="font-medium capitalize">{{ $application->gender ?: '—' }}</dd></div>
                            <div><dt class="text-ink-faint">Phone</dt><dd class="font-medium tabular-nums">{{ $application->phone ?: '—' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-ink-faint">Address</dt><dd class="font-medium">{{ $application->address ?: '—' }}</dd></div>
                        </dl>
                    </article>

                    <article class="panel">
                        <header class="panel-header"><h3 class="text-sm font-semibold">Educational background</h3><a href="{{ route('applicant.application', ['step' => 'education']) }}" class="text-xs font-medium text-pine hover:underline">Edit</a></header>
                        <dl class="grid gap-x-8 gap-y-3 px-5 py-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-ink-faint">Qualification</dt><dd class="font-medium uppercase">{{ strtoupper($application->qualification ?: '—') }}</dd></div>
                            <div><dt class="text-ink-faint">Examination year</dt><dd class="font-medium tabular-nums">{{ $application->examination_year }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-ink-faint">School</dt><dd class="font-medium">{{ $application->previous_school ?: '—' }}</dd></div>
                        </dl>
                    </article>

                    <article class="panel">
                        <header class="panel-header"><h3 class="text-sm font-semibold">Programme choices</h3><a href="{{ route('applicant.application', ['step' => 'choices']) }}" class="text-xs font-medium text-pine hover:underline">Edit</a></header>
                        <ol class="divide-y divide-line-soft">
                            @foreach ($application->choices as $choice)
                                <li class="flex items-center justify-between px-5 py-3 text-sm">
                                    <span class="font-medium">{{ $choice->programme?->name ?? '—' }}</span>
                                    <span class="badge-neutral">{{ ['First', 'Second'][$choice->rank - 1] }} choice</span>
                                </li>
                            @empty
                                <li class="px-5 py-3 text-sm text-ink-soft">No programme selected yet.</li>
                            @endforeach
                        </ol>
                    </article>

                    <article class="panel">
                        <header class="panel-header"><h3 class="text-sm font-semibold">Documents</h3><a href="{{ route('applicant.application', ['step' => 'documents']) }}" class="text-xs font-medium text-pine hover:underline">Edit</a></header>
                        <ul class="divide-y divide-line-soft">
                            @forelse ($application->documents as $document)
                                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                    <span>{{ $document->typeLabel() }} <span class="text-xs text-ink-faint">({{ $document->original_name }})</span></span>
                                    <span class="badge {{ match ($document->verification->value) { 'verified' => 'badge-success', 'rejected' => 'badge-danger', default => 'badge-warning' } }}">{{ $document->verification->label() }}</span>
                                </li>
                            @empty
                                <li class="px-5 py-3 text-sm text-ink-soft">No documents uploaded yet.</li>
                            @endforelse
                        </ul>
                    </article>

                    <form method="POST" action="{{ route('applicant.application.submit') }}" class="panel space-y-4 p-6">
                        @csrf
                        <label class="flex cursor-pointer items-start gap-2.5 text-sm leading-relaxed">
                            <input type="checkbox" name="declare" value="1" required class="mt-1 size-4 accent-[var(--color-pine)]" />
                            <span>I declare that the information provided is true and complete, and that all documents are authentic. I understand that false information voids any offer made.</span>
                        </label>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line-soft pt-4">
                            @php $canSubmit = $blockers === []; @endphp
                            <p class="text-xs leading-relaxed text-ink-faint">
                                After submitting, you pay the ₦10,000 application fee and track status from your dashboard.
                            </p>
                            <button type="submit" class="btn-primary btn-lg" @unless($canSubmit) disabled title="Resolve the outstanding items first" @endunless>Submit application</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        {{-- Context sidebar --}}
        <aside class="space-y-6">
            <section class="panel px-5 py-5">
                <h2 class="text-sm font-semibold">Progress</h2>
                @php
                    $done = collect([
                        'personal' => filled($application->first_name) && filled($application->last_name) && filled($application->gender) && filled($application->phone),
                        'education' => filled($application->qualification),
                        'choices' => $application->choices()->count() > 0,
                        'documents' => collect(['passport_photograph', 'olevel_result', 'birth_certificate'])->every(fn ($t) => $application->documents->contains('type', $t)),
                    ]);
                    $completed = $done->filter()->count();
                @endphp
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($steps as $key => $label)
                        @continue($key === 'review')
                        <li class="flex items-center gap-2.5">
                            <span class="flex size-5 items-center justify-center rounded-full {{ $done[$key] ? 'bg-success-tint text-success' : 'bg-paper-deep text-ink-faint' }}">
                                <x-lucide-check class="size-3" @unless($done[$key]) x-cloak @endunless style="@unless($done[$key]) display:none @endunless" />
                                @unless($done[$key])<span class="text-[0.625rem] font-bold">{{ $loop->index + 1 }}</span>@endunless
                            </span>
                            <span class="{{ $done[$key] ? '' : 'text-ink-faint' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-xs text-ink-faint tabular-nums">{{ $completed }} of 4 sections complete</p>
            </section>

            <section class="panel px-5 py-5">
                <h2 class="text-sm font-semibold">Need a hand?</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                    The admissions office answers application questions within two working days.
                </p>
                <a href="/support" class="btn-secondary btn-sm mt-4 w-full">Contact support</a>
            </section>
        </aside>
    </div>
</x-layout.portal>
