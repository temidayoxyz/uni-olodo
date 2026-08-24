<x-layout.portal title="Checkout — simulated gateway">
    <section class="panel mx-auto max-w-md px-6 py-8">
        <div class="flex items-center justify-between border-b border-line-soft pb-4">
            <p class="font-display text-lg font-semibold">OlodoPay <span class="badge-ochre badge align-middle">Simulated</span></p>
            <p class="text-xs text-ink-faint">Secure checkout</p>
        </div>

        <dl class="mt-5 space-y-2.5 text-sm">
            <div class="flex justify-between"><dt class="text-ink-soft">Invoice</dt>
                <dd class="tabular-nums font-medium">{{ $transaction->invoice->number }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-soft">Description</dt>
                <dd class="max-w-[60%] text-end font-medium">{{ $transaction->invoice->title }}</dd></div>
            <div class="flex justify-between border-t border-line-soft pt-2.5"><dt class="font-semibold">Amount</dt>
                <dd class="font-display text-xl font-bold tabular-nums">₦{{ number_format($transaction->amount / 100, 2) }}</dd></div>
        </dl>

        @if (session('error'))
            <div class="mt-4"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>
        @endif

        <form method="POST" action="{{ route('payments.complete', $transaction->reference) }}" class="mt-6 space-y-3">
            @csrf
            <p class="rounded-[var(--radius-control)] bg-paper-deep px-3.5 py-2.5 text-xs leading-relaxed text-ink-soft">
                This is the development gateway — no real money moves. Completing here runs the same
                server-side verification a production provider would require.
            </p>
            <button type="submit" class="btn-primary btn-lg w-full">Complete payment</button>
            <a href="{{ route('payments.show', $transaction->invoice) }}" class="btn-secondary w-full">Cancel</a>
        </form>

        <p class="mt-4 text-center text-xs text-ink-faint">Reference <span class="tabular-nums">{{ $transaction->reference }}</span></p>
    </section>
</x-layout.portal>
