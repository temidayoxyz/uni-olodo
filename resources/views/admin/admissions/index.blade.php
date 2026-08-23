<x-layout.portal title="Admissions queue">
    <x-ui.page-header
        title="Admissions"
        subtitle="Applications move through: submitted → under review → decision. Every action is audited." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    {{-- Status tabs --}}
    <nav class="mb-4 flex flex-wrap items-center gap-1" aria-label="Filter by status">
        <a href="{{ route('admin.admissions.index') }}" @if(! $status) aria-current="page" @endif
           class="rounded-full px-3.5 py-1.5 text-sm font-medium {{ ! $status ? 'bg-pine text-white' : 'text-ink-soft hover:bg-paper-deep' }}">
            All <span class="tabular-nums opacity-70">{{ number_format($counts->sum()) }}</span>
        </a>
        @foreach ([
            'submitted' => 'Submitted',
            'under_review' => 'In review',
            'more_info_required' => 'Info requested',
            'accepted' => 'Accepted',
        ] as $key => $label)
            <a href="{{ route('admin.admissions.index', ['status' => $key]) }}" @if($status === $key) aria-current="page" @endif
               class="rounded-full px-3.5 py-1.5 text-sm font-medium {{ $status === $key ? 'bg-pine text-white' : 'text-ink-soft hover:bg-paper-deep' }}">
                {{ $label }} <span class="tabular-nums opacity-70">{{ number_format($counts[$key]) }}</span>
            </a>
        @endforeach
        <span class="mx-2 text-line">|</span>
        <span class="text-sm text-ink-faint">{{ number_format($counts['decided_other']) }} decided/withdrawn</span>

        <form method="GET" action="{{ route('admin.admissions.index') }}" class="ms-auto flex items-center gap-2">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name or number…"
                   class="input w-56 !py-1.5 text-xs" aria-label="Search applications">
            <button type="submit" class="btn-secondary btn-sm">Search</button>
        </form>
    </nav>

    @if ($applications->isEmpty())
        <x-ui.empty-state icon="inbox-check" title="Nothing here right now">
            {{ $search !== '' ? 'No applications match that search.' : ($status ? 'No applications currently in this state.' : 'The queue is clear — new submissions will appear here.') }}
        </x-ui.empty-state>
    @else
        <div class="table-wrap">
            <table class="table min-w-[44rem]">
                <thead>
                    <tr>
                        <th scope="col">Applicant</th>
                        <th scope="col">First choice</th>
                        <th scope="col">Submitted</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td>
                                <a href="{{ route('admin.admissions.show', $application) }}" class="font-medium hover:text-pine">
                                    {{ $application->first_name }} {{ $application->last_name }}
                                </a>
                                <p class="text-xs tabular-nums text-ink-faint">{{ $application->number }}</p>
                            </td>
                            <td class="text-sm text-ink-soft">{{ $application->choices->first()?->programme?->name ?? '—' }}</td>
                            <td class="text-sm tabular-nums text-ink-soft">{{ $application->submitted_at?->format('j M Y') ?? '—' }}</td>
                            <td>
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
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.admissions.show', $application) }}" class="btn-secondary btn-sm">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-ui.pagination :paginator="$applications" />
    @endif
</x-layout.portal>
