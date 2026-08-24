<x-layout.portal :title="'Assignments — '.$offering->course->code">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.index') }}" class="hover:text-pine">{{ $managing ? 'My courses' : 'Courses' }}</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('courses.home', $offering) }}" class="tabular-nums hover:text-pine">{{ $offering->course->code }}</a>
        <span aria-hidden="true"> / </span>
        <span>Assignments</span>
    </nav>

    <x-ui.page-header
        title="Assignments"
        :subtitle="$offering->course->title" />

    @if ($assignments->isEmpty())
        <x-ui.empty-state icon="clipboard-list" title="No assignments yet">
            {{ $managing ? 'Create assignments from the offering workspace when teaching begins.' : 'Assignments will appear here once your lecturer publishes them.' }}
        </x-ui.empty-state>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Assignment</th>
                        <th scope="col">Due</th>
                        <th scope="col">Points</th>
                        <th scope="col">{{ $managing ? 'Grading' : 'Your status' }}</th>
                        <th scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        @php
                            $submission = $managing ? null : $assignment->submissions->first();
                            $overdue = now()->isAfter($assignment->due_at);
                        @endphp
                        <tr>
                            <td class="font-medium">
                                <a href="{{ route('courses.assignment.show', [$offering, $assignment]) }}" class="hover:text-pine">{{ $assignment->title }}</a>
                            </td>
                            <td class="tabular-nums text-sm {{ $overdue ? 'text-ink-faint' : '' }}">
                                {{ $assignment->due_at->format('D j M, g:i a') }}
                                @unless($overdue)<span class="block text-xs text-ink-faint">in {{ $assignment->due_at->diffInDays(now()) === 0 ? 'less than a day' : $assignment->due_at->diffInDays(now()).' days' }}</span>@endunless
                            </td>
                            <td class="num">{{ number_format($assignment->points, 0) }}</td>
                            <td class="text-sm">
                                @if ($managing)
                                    @if ($assignment->pending_count > 0)
                                        <a href="{{ route('courses.grading', [$offering, $assignment]) }}" class="badge-warning badge hover:brightness-95">
                                            <span class="dot"></span> {{ $assignment->pending_count }} to grade
                                        </a>
                                    @else
                                        <span class="badge-neutral">{{ $assignment->submissions_count }} submitted · graded</span>
                                    @endif
                                @elseif ($submission?->graded_at)
                                    <span class="badge-success tabular-nums">Graded {{ number_format($submission->score, 0) }}/{{ $assignment->points }}</span>
                                @elseif ($submission)
                                    <span class="badge-info">Submitted {{ $submission->submitted_at->diffForHumans() }}</span>
                                @elseif ($overdue)
                                    <span class="badge-danger badge">Missed</span>
                                @else
                                    <span class="badge-warning badge">Not submitted</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($managing && $assignment->submissions_count > 0)
                                    <a href="{{ route('courses.grading', [$offering, $assignment]) }}" class="btn-secondary btn-sm">Grade</a>
                                @else
                                    <a href="{{ route('courses.assignment.show', [$offering, $assignment]) }}" class="btn-secondary btn-sm">Open</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layout.portal>
