<x-layout.portal :title="$programme->exists ? 'Edit programme' : 'New programme'">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('admin.academics') }}" class="hover:text-pine">Academic structure</a>
        <span aria-hidden="true"> / </span>
        <span>{{ $programme->exists ? 'Edit '.$programme->name : 'New programme' }}</span>
    </nav>

    <x-ui.page-header :title="$programme->exists ? 'Edit — '.$programme->name : 'Create a programme'"
        subtitle="Programme details feed the public academics pages, admissions choices, and tuition invoicing." />

    <form method="POST"
          action="{{ $programme->exists ? route('admin.programmes.update', $programme) : route('admin.programmes.store') }}"
          class="panel max-w-3xl space-y-5 px-6 py-6">
        @csrf
        @if ($programme->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.select label="Department" name="department_id" required
                        :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->name.' ('.$d->faculty->code.')'])"
                        :selected="(string) old('department_id', $programme->department_id)" placeholder="Select department…" />
            <x-ui.input label="Code" name="code" required :value="old('code', $programme->code)"
                        placeholder="e.g. CSC-BS" hint="Unique catalogue code." />
        </div>

        <x-ui.input label="Programme name" name="name" required :value="old('name', $programme->name)"
                    placeholder="e.g. B.Sc. Computer Science" />

        <div class="grid gap-5 sm:grid-cols-3">
            <x-ui.select label="Award" name="award" required
                        :options="['bsc' => 'B.Sc.', 'beng' => 'B.Eng.']"
                        :selected="old('award', $programme->award ?? 'bsc')" />
            <x-ui.select label="Duration (semesters)" name="duration_semesters" required
                        :options="collect(range(2, 12, 2))->combine(collect(range(2, 12, 2)))->all()"
                        :selected="(string) old('duration_semesters', $programme->duration_semesters ?? 8)" />
            <x-ui.input label="Tuition per session (₦)" name="tuition_per_session_naira" type="number" step="1000" min="0" required
                        :value="old('tuition_per_session_naira', $programme->exists ? $programme->tuition_per_session / 100 : 350000)"
                        hint="Stored in kobo internally." />
        </div>

        <x-ui.textarea label="Description (public page)" name="description" rows="4" :value="old('description', $programme->description)" />
        <x-ui.textarea label="Entry requirements (public page)" name="entry_requirements" rows="3" :value="old('entry_requirements', $programme->entry_requirements)" />

        <label class="flex items-center gap-2.5 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $programme->exists ? $programme->is_active : true) ? 'checked' : '' }}
                   class="size-4 accent-[var(--color-pine)]">
            Active — open for admission and registration
        </label>

        <div class="flex justify-end gap-2 border-t border-line-soft pt-4">
            <a href="{{ route('admin.academics') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $programme->exists ? 'Save changes' : 'Create programme' }}</button>
        </div>
    </form>
</x-layout.portal>
