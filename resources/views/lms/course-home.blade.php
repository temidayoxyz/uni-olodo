<x-layout.portal :title="$offering->course->code.' — '.$offering->course->title">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('courses.index') }}" class="hover:text-pine">{{ $managing ? 'My courses' : 'Courses' }}</a>
        <span aria-hidden="true"> / </span>
        <span class="tabular-nums">{{ $offering->course->code }}</span>
    </nav>

    <x-ui.page-header
        :title="$offering->course->code.' — '.$offering->course->title"
        :subtitle="($managing ? 'Teaching · '.number_format($enrolledCount).' students enrolled' : ($offering->lecturer?->name ?? 'Staff TBA').' · '.$offering->course->credit_units.' credits')" />

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        {{-- Modules --}}
        <section aria-labelledby="modules-heading" class="panel">
            <div class="panel-header">
                <h2 id="modules-heading" class="text-sm font-semibold">Course modules</h2>
            </div>

            @if ($modules->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-ink-soft">No materials have been published yet.</p>
            @else
                <ol class="divide-y divide-line-soft">
                    @foreach ($modules as $module)
                        <li>
                            <a href="{{ route('courses.module', [$offering, $module]) }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-surface-dim">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-pine-tint text-sm font-bold text-pine tabular-nums">
                                    {{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold group-hover:text-pine">{{ $module->title }}</p>
                                    @if ($module->summary)
                                        <p class="mt-0.5 line-clamp-1 text-xs text-ink-faint">{{ $module->summary }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 text-xs text-ink-faint tabular-nums">{{ $module->contents->count() }} items</span>
                                <x-lucide-chevron-right class="size-4 shrink-0 text-ink-faint group-hover:text-pine" />
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- Deadlines / activity --}}
        <aside class="space-y-6">
            <section class="panel" aria-labelledby="activity-heading">
                <div class="panel-header">
                    <h2 id="activity-heading" class="text-sm font-semibold">{{ $managing ? 'Assignments & grading' : 'Deadlines & quizzes' }}</h2>
                    <a href="{{ route('courses.assignments', $offering) }}" class="text-xs font-medium text-pine hover:underline">All →</a>
                </div>

                @forelse ($items as $item)
                    <div class="border-b border-line-soft px-5 py-3.5 last:border-b-0">
                        @if ($item->type === 'assignment')
                            <p class="flex items-baseline justify-between gap-3 text-sm">
                                <a href="{{ route('courses.assignment.show', [$offering, $item->model]) }}" class="min-w-0 truncate font-semibold hover:text-pine">
                                    {{ $item->model->title }}
                                </a>
                                <span class="shrink-0 text-xs tabular-nums {{ \Carbon\Carbon::parse($item->model->due_at)->diffInDays() <= 3 ? 'font-bold text-danger' : 'text-ink-faint' }}">
                                    {{ \Carbon\Carbon::parse($item->model->due_at)->diffInDays() === 0 ? 'Due today' : 'due in '.\Carbon\Carbon::parse($item->model->due_at)->diffInDays().'d' }}
                                </span>
                            </p>
                            <p class="mt-1 flex items-center gap-2 text-xs text-ink-faint">
                                @if ($managing)
                                    {{ $item->model->pending_count }} of {{ $item->model->submissions_count ?? '—' }} submissions awaiting grades
                                    <a href="{{ route('courses.grading', [$offering, $item->model]) }}" class="font-medium text-pine hover:underline">Open queue →</a>
                                @elseif ($item->submission)
                                    Submitted {{ $item->submission->submitted_at->diffForHumans() }}
                                    @if ($item->submission->graded_at)
                                        <span class="badge-success">Graded {{ number_format($item->submission->score, 0) }}/{{ $item->model->points }}</span>
                                    @else
                                        <span class="badge-info">Awaiting grade</span>
                                    @endif
                                @else
                                    <span class="badge-warning badge">Not submitted</span>
                                @endif
                            </p>
                        @else
                            <p class="flex items-baseline justify-between gap-3 text-sm font-semibold">
                                {{ $item->model->title }}
                                <span class="shrink-0 text-xs font-normal tabular-nums text-ink-faint">
                                    opens {{ $item->model->available_from?->diffForHumans() }}
                                </span>
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-ink-soft">Nothing scheduled right now.</p>
                @endforelse
            </section>
        </aside>
    </div>
</x-layout.portal>
