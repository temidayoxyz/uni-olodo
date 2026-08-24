<x-layout.portal title="Bursary — payments">
    <x-ui.page-header title="Payments"
        subtitle="Invoices across the university, and manual bank transfers awaiting verification." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif

    <div class="mb-8 grid gap-4 sm:grid-cols-2">
        <div class="panel px-5 py-4">
            <p class="text-xs tracking-wide text-ink-faint uppercase">Collected (paid invoices)</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums">₦{{ number_format($totals['collected'] / 100, 2) }}</p>
        </div>
        <div class="panel px-5 py-4">
            <p class="text-xs tracking-wide text-ink-faint uppercase">Outstanding</p>
            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ochre-strong">₦{{ number_format($totals['outstanding'] / 100, 2) }}</p>
        </div>
    </div>

    @if ($pendingManual->isNotEmpty())
        <section aria-labelledby="manual-heading" class="mb-10">
            <h2 id="manual-heading" class="mb-3 text-sm font-semibold text-ink-soft">Manual transfers awaiting verification</h2>
            <div class="space-y-3">
                @foreach ($pendingManual as $transaction)
                    <article class="panel flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">{{ $transaction->invoice->user->name }} · {{ $transaction->invoice->number }}</p>
                            <p class="text-xs tabular-nums text-ink-faint">{{ $transaction->reference }} · ₦{{ number_format($transaction->amount / 100, 2) }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.payments.verify', $transaction) }}">
                            @csrf
                            <button type="submit" class="btn-primary btn-sm"
                                    onclick="return confirm('Confirm the teller has been checked against the bank? This settles the invoice.')">
                                Verify transfer
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section aria-labelledby="invoices-heading">
        <h2 id="invoices-heading" class="mb-3 text-sm font-semibold text-ink-soft">All invoices</h2>
        <div class="table-wrap">
            <table class="table min-w-[44rem]">
                <thead>
                    <tr><th scope="col">Invoice</th><th scope="col">Account holder</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Paid at</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td>
                                <span class="font-medium tabular-nums">{{ $invoice->number }}</span>
                                <p class="text-xs text-ink-faint">{{ $invoice->title }}</p>
                            </td>
                            <td class="text-sm">{{ $invoice->user->name }}</td>
                            <td class="num font-semibold">{{ $invoice->formattedAmount() }}</td>
                            <td><span class="{{ $invoice->isPaid() ? 'badge-success' : 'badge-warning badge' }}">{{ ucfirst($invoice->status) }}</span></td>
                            <td class="text-xs tabular-nums text-ink-faint">{{ $invoice->paid_at?->format('j M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-ui.pagination :paginator="$invoices" />
    </section>
</x-layout.portal>
