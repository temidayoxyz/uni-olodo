<x-layout.portal title="Applicant portal">
    <x-ui.page-header
        title="Your admission journey"
        subtitle="Everything about your application in one place. The admissions office will contact you here if anything more is needed." />

    @if (! $application)
        {{-- No application started yet --}}
        <section class="panel mx-auto max-w-2xl px-6 py-12 text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-pine-tint text-pine">
                <x-lucide-file-plus-2 class="size-6" />
            </div>
            <h2 class="mt-4 font-display text-xl font-semibold">Start your application</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-ink-soft">
                Your account is ready but no application has been started. When you begin,
                you can save your progress and return at any time before submitting.
            </p>
            <a href="/applicant/application" class="btn-primary btn-lg mt-6">Begin application</a>
        </section>
    @else
        @php
            $status = $application->status;
            $statusStyles = [
                'draft' => 'badge-neutral', 'submitted' => 'badge-info',
                'under_review' => 'badge-info', 'more_info_required' => 'badge-warning',
                'accepted' => 'badge-success', 'conditionally_accepted' => 'badge-success',
                'waitlisted' => 'badge-ochre', 'rejected' => 'badge-danger',
                'withdrawn' => 'badge-neutral', 'enrolled' => 'badge-pine',
            ];
        @endphp

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Status card --}}
                <section class="panel px-5 py-5" aria-labelledby="status-heading">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="status-heading" class="text-sm font-semibold text-ink-soft">Application {{ $application->number }}</h2>
                            <p class="mt-1 font-display text-xl font-semibold tracking-tight">
                                {{ $status->label() }}
                            </p>
                        </div>
                        <span class="{{ $statusStyles[$status->value] }} mt-1">{{ $status->label() }}</span>
                    </div>

                    <div class="divider my-4"></div>

                    @if ($status === \App\Enums\ApplicationStatus::Draft)
                        <p class="text-sm leading-relaxed text-ink-soft">
                            This application is a draft — it hasn't been submitted yet.
                            Complete every section, review, and submit when you're ready.
                        </p>
                        <a href="/applicant/application" class="btn-primary mt-4">Resume application</a>
                    @elseif ($status === \App\Enums\ApplicationStatus::MoreInfoRequired)
                        <x-ui.alert type="warning" title="The admissions office needs something from you">
                            {{ $application->decision_note }}
                        </x-ui.alert>
                        <a href="{{ route('applicant.application', ['step' => 'documents']) }}" class="btn-primary mt-4">Upload documents</a>
                    @elseif ($status->isDecided())
                        <x-ui.alert type="success" title="{{ $status->label() }}">
                            {{ $application->decision_note ?? 'A decision has been made on your application.' }}
                        </x-ui.alert>

                        @if (in_array($status, [\App\Enums\ApplicationStatus::Accepted, \App\Enums\ApplicationStatus::ConditionallyAccepted]) && $application->offer_responded_at === null)
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('applicant.offer.accept') }}">
                                    @csrf
                                    <button type="submit" class="btn-primary">Accept offer</button>
                                </form>
                                <form method="POST" action="{{ route('applicant.offer.decline') }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary">Decline offer</button>
                                </form>
                            </div>
                        @endif
                    @else
                        <p class="text-sm leading-relaxed text-ink-soft">
                            Your application was received on {{ $application->submitted_at?->format('j F Y') }}
                            and is currently with the admissions office. You'll be notified here and by email
                            the moment its status changes — there is nothing else you need to do right now.
                        </p>
                    @endif
                </section>

                {{-- Programme choices --}}
                <section class="panel" aria-labelledby="choices-heading">
                    <div class="panel-header"><h2 id="choices-heading" class="text-sm font-semibold">Programme choices</h2></div>
                    <ul class="divide-y divide-line-soft">
                        @foreach ($application->choices as $choice)
                            <li class="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <p class="text-sm font-semibold">{{ $choice->programme->name }}</p>
                                    <p class="text-xs text-ink-faint">{{ $choice->programme->department->name }}</p>
                                </div>
                                <span class="badge-neutral">{{ ['First', 'Second', 'Third'][$choice->rank - 1] ?? "#{$choice->rank} choice" }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            {{-- Side column --}}
            <div class="space-y-6">
                <section class="panel px-5 py-5">
                    <h2 class="text-sm font-semibold">Documents</h2>
                    @if ($application->documents->isEmpty())
                        <p class="mt-3 text-sm leading-relaxed text-ink-soft">
                            No documents uploaded yet. Most applications need a passport photograph,
                            your O-level result, and an entrance examination slip.
                        </p>
                        <a href="{{ route('applicant.application', ['step' => 'documents']) }}" class="btn-secondary btn-sm mt-4 w-full">Manage documents</a>
                    @else
                        <ul class="mt-3 space-y-2.5">
                            @foreach ($application->documents as $document)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="truncate">{{ $document->typeLabel() }}</span>
                                    <span class="badge {{ match ($document->verification->value) {
                                        'verified' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        default => 'badge-warning',
                                    } }}">{{ $document->verification->label() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if ($invoice && ! $invoice->isPaid())
                    <x-ui.alert type="warning" title="{{ $invoice->title }}">
                        {{ $invoice->formattedAmount() }} due {{ $invoice->due_at?->format('j M Y') }}.
                        <a href="/applicant/payments" class="ms-1 font-semibold underline underline-offset-2">Pay now</a>
                    </x-ui.alert>
                @endif

                <section class="panel px-5 py-5">
                    <h2 class="text-sm font-semibold">Need help?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                        Questions about requirements or your application? The admissions team answers within two working days.
                    </p>
                    <a href="/support" class="btn-secondary btn-sm mt-4 w-full">Contact support</a>
                </section>
            </div>
        </div>
    @endif
</x-layout.portal>
