<x-layout.portal title="Start application">
    <section class="panel mx-auto max-w-2xl px-6 py-12 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-pine-tint text-pine">
            <x-lucide-file-plus-2 class="size-6" />
        </div>
        <h1 class="mt-4 font-display text-xl font-semibold">Begin your application</h1>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-ink-soft">
            You'll work through five short steps — personal details, educational background,
            programme choices, documents, and a final review. Your progress saves as you go,
            and you can leave and return at any time before submitting.
        </p>
        <ul class="mx-auto mt-5 max-w-sm space-y-2 text-start text-sm">
            @foreach ([
                'A passport photograph (jpg/png)',
                'Your O-level result (WAEC/NECO/NABTEB) as PDF or scan',
                'Birth certificate or declaration of age',
                '₦10,000 application fee, payable after submission',
            ] as $item)
                <li class="flex gap-2.5"><x-lucide-check class="mt-0.5 size-4 shrink-0 text-success" /> {{ $item }}</li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('applicant.application.start') }}" class="mt-7">
            @csrf
            <button type="submit" class="btn-primary btn-lg">Create my application</button>
        </form>
    </section>
</x-layout.portal>
