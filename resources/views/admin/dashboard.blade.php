<x-layout.portal title="Administration">
    <x-ui.page-header
        title="Administration"
        :subtitle="$semester ? 'Active term · '.$semester->session->name.' '.$semester->name.' (registration closes '.$semester->registration_closes_at?->format('j M Y').')' : 'No active semester'" />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Work queues --}}
        <div class="space-y-6 lg:col-span-2">
            <section aria-labelledby="queues-heading">
                <h2 id="queues-heading" class="mb-3 text-sm font-semibold text-ink-soft">Needs attention</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @forelse ($queues as $label => $count)
                        @php $urls = [
                            'Applications awaiting review' => '/admin/admissions?status=submitted',
                            'Result submissions awaiting approval' => '/admin/results',
                        ]; @endphp
                        <a href="{{ $urls[$label] ?? '/admin' }}" class="panel group flex items-center justify-between px-5 py-4 transition-colors hover:border-pine-line hover:bg-surface-dim">
                            <span class="text-sm font-medium">{{ $label }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-xl font-semibold tabular-nums {{ $count > 0 ? 'text-ochre-strong' : 'text-ink-faint' }}">{{ number_format($count) }}</span>
                                <x-lucide-chevron-right class="size-4 text-ink-faint group-hover:text-pine" />
                            </span>
                        </a>
                    @empty
                        <p class="panel px-5 py-8 text-center text-sm text-ink-soft sm:col-span-2">Nothing is waiting on you right now.</p>
                    @endforelse
                </div>
            </section>

            @if ($recentApplications->isNotEmpty())
                <section aria-labelledby="recent-apps-heading" class="panel">
                    <div class="panel-header">
                        <h2 id="recent-apps-heading" class="text-sm font-semibold">Latest applications in the queue</h2>
                        <a href="/admin/admissions" class="text-xs font-medium text-pine hover:underline">Open admissions →</a>
                    </div>
                    <ul class="divide-y divide-line-soft">
                        @foreach ($recentApplications as $application)
                            <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $application->first_name }} {{ $application->last_name }}</p>
                                    <p class="text-xs tabular-nums text-ink-faint">{{ $application->number }} · submitted {{ $application->submitted_at?->diffForHumans() }}</p>
                                </div>
                                <span class="badge-info badge">{{ $application->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        {{-- Areas --}}
        <aside aria-labelledby="areas-heading" class="space-y-6">
            <section class="panel px-5 py-5">
                <h2 id="areas-heading" class="text-sm font-semibold">Go to</h2>
                <nav class="mt-4 grid gap-1.5">
                    @foreach (\App\Support\Navigation::for(auth()->user()) as $section)
                        @foreach ($section['items'] as $item)
                            @continue(str_contains($item['url'], 'dashboard') || str_ends_with($item['url'], '/admin'))
                            <a href="{{ $item['url'] }}" class="flex items-center gap-2.5 rounded-[var(--radius-control)] px-3 py-2 text-sm font-medium hover:bg-pine-tint hover:text-pine">
                                <x-lucide-{{ $item['icon'] }} class="size-4 opacity-80" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    @endforeach
                </nav>
            </section>
        </aside>
    </div>
</x-layout.portal>
