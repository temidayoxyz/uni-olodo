<x-layout.portal title="Results approvals">
    <x-ui.page-header title="Results"
        subtitle="Approve lecturer submissions, then publish to make grades official. Publication writes permanent records and notifies students." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    <section aria-labelledby="pending-heading" class="mb-10">
        <h2 id="pending-heading" class="mb-3 text-sm font-semibold text-ink-soft">Awaiting approval</h2>

        @forelse ($pending as $submission)
            <article class="panel mb-3 flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold">
                        {{ $submission->offering->course->code }} — {{ $submission->offering->course->title }}
                    </p>
                    <p class="mt-0.5 text-xs text-ink-faint">
                        {{ $submission->offering->semester?->session?->name }} {{ $submission->offering->semester?->name }}
                        · submitted by {{ $submission->submitter?->name }} {{ $submission->submitted_at?->diffForHumans() }}
                    </p>
                </div>
                <a href="{{ route('admin.results.show', $submission) }}" class="btn-primary btn-sm">Review</a>
            </article>
        @empty
            <x-ui.empty-state icon="inbox-check" title="Nothing awaiting approval">
                When lecturers submit provisional results, they arrive here for review.
            </x-ui.empty-state>
        @endforelse
    </section>

    <section aria-labelledby="recent-heading">
        <h2 id="recent-heading" class="mb-3 text-sm font-semibold text-ink-soft">Recently handled</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th scope="col">Offering</th><th scope="col">Submitted by</th><th scope="col">Status</th><th scope="col">Reviewed</th></tr>
                </thead>
                <tbody>
                    @foreach ($recent as $submission)
                        <tr>
                            <td class="font-medium">{{ $submission->offering->course->code }} — {{ $submission->offering->course->title }}</td>
                            <td class="text-sm text-ink-soft">{{ $submission->submitter?->name }}</td>
                            <td><span class="{{ match ($submission->status->value) { 'published' => 'badge-success', 'approved' => 'badge-pine', default => 'badge-warning' } }}">{{ $submission->status->label() }}</span></td>
                            <td class="text-xs tabular-nums text-ink-faint">
                                {{ $submission->reviewed_at?->format('j M Y') }}@if($submission->reviewer) · {{ $submission->reviewer->name }}@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layout.portal>
