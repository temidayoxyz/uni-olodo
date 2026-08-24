<x-layout.portal :title="$invoice->number">
    <nav aria-label="Breadcrumb" class="mb-3 text-xs text-ink-faint">
        <a href="{{ route('payments.index') }}" class="hover:text-pine">Payments</a>
        <span aria-hidden="true"> / </span>
        <span class="tabular-nums">{{ $invoice->number }}</span>
    </nav>

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif

    <div class="mx-auto max-w-2xl">
        {{-- Invoice header --}}
        <section class="panel px-6 py-6" role="document" aria-label="Invoice {{ $invoice->number }}">
            <div class="flex items-start justify-between gap-4 border-b border-line-soft pb-4">
                <div>
                    <p class="text-xs font-bold tracking-widest text-pine uppercase">University of Olodo · Invoice</p>
                    <p class="mt-1 font-display text-2xl font-semibold tabular-nums">{{ $invoice->number }}</p>
                </div>
                <span class="{{ $invoice->isPaid() ? 'badge-success' : ($invoice->status === 'void' ? 'badge-neutral' : 'badge-warning') }} text-sm">{{ ucfirst($invoice->status) }}</span>
            </div>

            <dl class="grid grid-cols-2 gap-4 py-4 text-sm">
                <div><dt class="text-xs text-ink-faint">Billed to</dt><dd class="font-medium">{{ auth()->user()->name }}</dd></div>
                <div><dt class="text-xs text-ink-faint">Due date</dt><dd class="font-medium tabular-nums">{{ $invoice->due_at?->format('j F Y') ?? '—' }}</dd></div>
            </dl>

            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-y border-line bg-surface-dim">
                        <th class="py-1.5 text-start font-semibold">Description</th>
                        <th class="py-1.5 text-center font-semibold">Qty</th>
                        <th class="py-1.5 text-end font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr class="border-b border-line-soft">
                            <td class="py-2.5">{{ $item->description }}</td>
                            <td class="py-2.5 text-center tabular-nums">{{ $item->quantity }}</td>
                            <td class="py-2.5 text-end tabular-nums">₦{{ number_format($item->unit_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" class="pt-3 text-end font-semibold">Total due</td>
                        <td class="pt-3 text-end font-display text-lg font-bold tabular-nums">{{ $invoice->formattedAmount() }}</td>
                    </tr>
                </tbody>
            </table>

            @if (! $invoice->isPaid() && $invoice->status !== 'void')
                <form method="POST" action="{{ route('payments.pay', $invoice) }}" class="border-t border-line-soft pt-4 text-end">
                    @csrf
                    <button type="submit" class="btn-primary btn-lg">Pay ₦{{ number_format($invoice->amount_due / 100, 2) }}</button>
                </form>
            @endif
        </section>

        {{-- Payment history --}}
        @if ($invoice->transactions->isNotEmpty())
            <section class="panel mt-6 px-6 py-5" aria-labelledby="tx-heading">
                <h2 id="tx-heading" class="text-sm font-semibold">Payment history</h2>
                <ul class="mt-3 divide-y divide-line-soft">
                    @foreach ($invoice->transactions as $transaction)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                            <span class="tabular-nums text-xs text-ink-faint">{{ $transaction->reference }}</span>
                            <span class="tabular-nums font-medium">₦{{ number_format($transaction->amount / 100, 2) }}</span>
                            <span class="badge {{ match ($transaction->status) { 'verified' => 'badge-success', 'initiated', 'pending' => 'badge-warning badge', default => 'badge-danger' } }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                            @if ($transaction->verified_at)
                                <span class="text-xs tabular-nums text-ink-faint">{{ $transaction->verified_at->format('j M Y, g:i a') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layout.portal>
