<x-layout.portal title="Course catalogue">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('admin.academics') }}" class="hover:text-pine">Academic structure</a>
        <span aria-hidden="true"> / </span>
        <span>Courses</span>
    </nav>

    <x-ui.page-header title="Course catalogue"
        subtitle="The catalogue drives prerequisite checks, offerings, and registration. Deactivating a course removes it from future registration without touching history." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.courses') }}" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="department" class="input w-64 !py-1.5 text-sm" aria-label="Filter by department">
            <option value="">All departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected($activeDepartment?->id === $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary btn-sm">Filter</button>
    </form>

    {{-- Add course --}}
    <details class="panel mb-5 px-5 py-4">
        <summary class="cursor-pointer list-none text-sm font-semibold [&::-webkit-details-marker]:hidden">+ Add a course</summary>
        <form method="POST" action="{{ route('admin.courses.store') }}" class="mt-4 grid gap-4 sm:grid-cols-[auto_1fr_auto_auto_auto]">
            @csrf
            <x-ui.select name="department_id" required :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->code])"
                        :selected="(string) ($activeDepartment?->id ?? '')" placeholder="Dept" aria-label="Department" />
            <div class="grid grid-cols-[7rem_1fr] gap-3">
                <x-ui.input name="code" placeholder="CSC 401" required aria-label="Course code" />
                <x-ui.input name="title" placeholder="Course title" required aria-label="Course title" />
            </div>
            <x-ui.select name="credit_units" required :options="collect(range(1, 6))->combine(collect(range(1, 6)))->all()"
                        :selected="'3'" aria-label="Credit units" />
            <x-ui.select name="level" required
                        :options="['100' => '100', '200' => '200', '300' => '300', '400' => '400', '500' => '500']"
                        :selected="'300'" aria-label="Level" />
            <button type="submit" class="btn-primary btn-sm self-center">Add</button>
        </form>
    </details>

    <div class="table-wrap">
        <table class="table min-w-[44rem]">
            <thead>
                <tr>
                    <th scope="col">Code</th>
                    <th scope="col">Title</th>
                    <th scope="col">Department</th>
                    <th scope="col" class="text-center">Units</th>
                    <th scope="col" class="text-center">Level</th>
                    <th scope="col" class="text-end">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td class="font-medium tabular-nums">{{ $course->code }}</td>
                        <td>{{ $course->title }}</td>
                        <td class="text-xs text-ink-faint">{{ $course->department->code }} · {{ $course->department->faculty->code }}</td>
                        <td class="num">{{ $course->credit_units }}</td>
                        <td class="num">{{ $course->level }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.courses.toggle', $course) }}">
                                @csrf
                                <button type="submit" class="{{ $course->is_active ? 'badge-success badge hover:brightness-95' : 'badge-neutral cursor-pointer hover:bg-line' }}">
                                    {{ $course->is_active ? 'Active — deactivate' : 'Inactive — reactivate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-sm text-ink-soft">No courses match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$courses" />
</x-layout.portal>
