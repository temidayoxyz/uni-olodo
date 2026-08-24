<x-layout.portal title="Users & roles">
    <x-ui.page-header title="Users & roles"
        subtitle="Role and account-status changes take effect immediately and are permanently audited. Students become staff only through deliberate registry action." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    <nav class="mb-4 flex flex-wrap items-center gap-1" aria-label="Filter by role">
        <a href="{{ route('admin.users.index') }}" @if(! $activeRole) aria-current="page" @endif
           class="rounded-full px-3 py-1.5 text-sm font-medium {{ ! $activeRole ? 'bg-pine text-white' : 'text-ink-soft hover:bg-paper-deep' }}">All</a>
        @foreach ($roles as $role)
            <a href="{{ route('admin.users.index', ['role' => $role->value]) }}" @if($activeRole === $role->value) aria-current="page" @endif
               class="rounded-full px-3 py-1.5 text-sm font-medium {{ $activeRole === $role->value ? 'bg-pine text-white' : 'text-ink-soft hover:bg-paper-deep' }}">
                {{ $role->label() }} <span class="tabular-nums opacity-60">{{ $roleCounts[$role->value] }}</span>
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.users.index') }}" class="ms-auto flex items-center gap-2">
            @if ($activeRole)<input type="hidden" name="role" value="{{ $activeRole }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name or email…"
                   class="input w-56 !py-1.5 text-xs" aria-label="Search users">
            <button type="submit" class="btn-secondary btn-sm">Search</button>
        </form>
    </nav>

    <div class="table-wrap">
        <table class="table min-w-[40rem]">
            <thead>
                <tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="font-medium">{{ $user->name }}</td>
                        <td class="text-sm text-ink-soft">{{ $user->email }}</td>
                        <td><span class="badge-neutral">{{ $user->role->label() }}</span></td>
                        <td><span class="{{ $user->status->value === 'active' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($user->status->value) }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary btn-sm">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-ink-soft">No users match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$users" />
</x-layout.portal>
