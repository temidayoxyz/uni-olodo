<x-layout.public title="Contact">
    <section class="border-b border-line">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold tracking-widest text-pine uppercase">Contact</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                Talk to the right office, first time.
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink-soft">
                Enquiries sent through this page reach the registry and are routed to the
                office that can actually answer them. We reply within two working days.
            </p>
        </div>
    </section>

    <section>
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:gap-16 lg:px-8">
            {{-- Enquiry form --}}
            <div>
                <h2 class="font-display text-2xl font-semibold tracking-tight">Send an enquiry</h2>

                @if (session('status'))
                    <div class="mt-6">
                        <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input label="Your name" name="name" required :value="old('name')" placeholder="e.g. Adebayo Ogundimu" />
                        <x-ui.input label="Email address" name="email" type="email" required :value="old('email')" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-[1fr_2fr]">
                        <x-ui.input label="Phone (optional)" name="phone" :value="old('phone')" placeholder="080…" />
                        <x-ui.select label="Subject" name="subject" required :options="[
                            'Admissions enquiry' => 'Admissions enquiry',
                            'Application help' => 'Help with my application',
                            'Fees & payments' => 'Fees & payments',
                            'Academic records' => 'Academic records & transcripts',
                            'Technical support' => 'Technical support',
                            'Other' => 'Other',
                        ]" :selected="old('subject')" placeholder="Choose a subject…" />
                    </div>

                    <x-ui.textarea label="Message" name="message" required :value="old('message')" rows="6"
                                  hint="Include your application number if your enquiry is about an existing application." />

                    <button type="submit" class="btn-primary btn-lg">Send message</button>
                </form>
            </div>

            {{-- Offices --}}
            <aside aria-labelledby="offices-heading">
                <h2 id="offices-heading" class="text-sm font-bold tracking-wide uppercase">Offices</h2>
                <ul class="mt-6 space-y-6">
                    @foreach ($offices as $office)
                        <li>
                            <h3 class="font-semibold">{{ $office['name'] }}</h3>
                            <p class="text-sm text-ink-soft">{{ $office['handles'] }}</p>
                            <p class="mt-1 text-sm">
                                <a href="mailto:{{ $office['email'] }}" class="font-medium text-pine hover:underline">{{ $office['email'] }}</a>
                                <span class="tabular-nums text-ink-faint"> · {{ $office['phone'] }}</span>
                            </p>
                        </li>
                    @endforeach
                </ul>

                <div class="panel mt-8 px-5 py-4">
                    <h3 class="flex items-center gap-2 text-sm font-semibold"><x-lucide-map-pin class="size-4 text-pine" /> Visit</h3>
                    <address class="mt-2 text-sm leading-relaxed not-italic text-ink-soft">
                        Registry building, Oladipo Alayande Road,<br/>
                        Olodo, Ibadan, Oyo State<br/>
                        <span class="text-xs text-ink-faint">Office hours: Mon–Fri, 8:00 am – 4:00 pm</span>
                    </address>
                </div>
            </aside>
        </div>
    </section>
</x-layout.public>
