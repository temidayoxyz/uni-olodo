<x-layout.portal title="Academic structure">
    <x-ui.page-header title="Academic structure"
        subtitle="Faculties, departments, programmes and the course catalogue. Changes take effect immediately across registration and the public site." />

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.programmes.create') }}" class="btn-primary btn-sm">New programme</a>
        <a href="{{ route('admin.courses') }}" class="btn-secondary btn-sm">Course catalogue</a>
        <a href="{{ route('admin.calendar') }}" class="btn-secondary btn-sm">Calendar & windows</a>
        <span class="ms-auto text-xs text-ink-faint tabular-nums">{{ $facultyCount ?? $faculties->count() }} faculties · {{ $programmeCount }} programmes · {{ $courseCount }} courses</span>
    </div>

    <div class="space-y-8">
        @foreach ($faculties as $faculty)
            <section aria-labelledby="f-{{ $faculty->id }}">
                <h2 id="f-{{ $faculty->id }}" class="border-b border-line pb-2 font-display text-lg font-semibold tracking-tight">
                    {{ $faculty->name }}
                    <span class="ms-1 text-xs font-normal text-ink-faint tabular-nums">{{ $faculty->code }}</span>
                </h2>

                <div class="mt-3 grid gap-4 lg:grid-cols-2">
                    @foreach ($faculty->departments as $department)
                        <div class="panel px-5 py-4">
                            <h3 class="text-sm font-semibold">{{ $department->name }}</h3>
                            <ul class="mt-2.5 space-y-1.5">
                                @forelse ($department->programmes as $programme)
                                    <li class="flex items-center justify-between gap-3 text-sm">
                                        <span>{{ $programme->name }}</span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            @unless ($programme->is_active)
                                                <span class="badge-neutral">Inactive</span>
                                            @endunless
                                            <a href="{{ route('admin.programmes.edit', $programme) }}" class="text-xs font-medium text-pine hover:underline">Edit</a>
                                        </span>
                                    </li>
                                @empty
                                    <li class="text-xs italic text-ink-faint">No programmes under this department.</li>
                                @endforelse
                            </ul>
                            <p class="mt-3 border-t border-line-soft pt-2 text-xs text-ink-faint tabular-nums">
                                {{ $department->courses->count() }} courses in catalogue ·
                                <a href="{{ route('admin.courses', ['department' => $department->id]) }}" class="font-medium text-pine hover:underline">manage →</a>
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-layout.portal>
