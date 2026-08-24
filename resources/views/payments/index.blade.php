<x-layout.portal title="Payments">
    <x-ui.page-header title="Payments & receipts"
        subtitle="Tuition and fee invoices, with verified payment history. Amounts are settled in a single instalment per invoice." />

    @if (session('status'))<div class="mb-5"><x-ui.alert type="success">{{ session('status') }}</x-ui.alert></div>@endif
    @if (session('error'))<div class="mb-5"><x-ui.alert type="danger">{{ session('error') }}</x-ui.alert></div>@endif

    @php $outstandingCount = $invoices->filter(fn ($i) => ! $i->isPaid())->count(); @endphp
    <p class="mb-4 text-sm text-ink-soft">
        Outstanding: <strong class="tabular-nums text-ink">₦{{ number_format($outstanding / 100, 2) }}</strong>
        across {{ $outstandingCount }} invoice{{ $outstandingCount === 1 ? '' : 's' }}.
    </p>

    <div class="table-wrap">
        <table class="table min-w-[40rem]">
            <thead>
                <tr><th scope="col">Invoice</th><th scope="col">Type</th><th scope="col">Amount</th><th scope="col">Due</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Action</span></th></tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('payments.show', $invoice) }}" class="font-medium tabular-nums hover:text-pine">{{ $invoice->number }}</a>
                            <p class="text-xs text-ink-faint">{{ $invoice->title }}</p>
                        </td>
                        <td class="text-sm">{{ $invoice->type->label() }}</td>
                        <td class="num font-semibold">{{ $invoice->formattedAmount() }}</td>
                        <td class="text-sm tabular-nums {{ $invoice->status === 'unpaid' && $invoice->due_at?->isPast() ? 'font-bold text-danger' : '' }}">
                            {{ $invoice->due_at?->format('j M Y') ?? '—' }}
                        </td>
                        <td><span class="{{ $invoice->isPaid() ? 'badge-success' : ($invoice->status === 'void' ? 'badge-neutral' : 'badge-warning badge') }}">{{ ucfirst($invoice->status) }}</span></td>
                        <td class="text-end">
                            @if (! $invoice->isPaid() && $invoice->status !== 'void')
                                <form method="POST" action="{{ route('payments.pay', $invoice) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-sm">Pay now</button>
                                </form>
                            @else
                                <a href="{{ route('payments.show', $invoice) }}" class="btn-secondary btn-sm">Receipt</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-sm text-ink-soft">No invoices on your account.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$invoices" />
</x-layout.portal>
