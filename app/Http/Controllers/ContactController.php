<?php

namespace App\Http\Controllers;

use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('public.contact', [
            'offices' => $this->offices(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:24'],
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'required' => 'This field is required.',
        ]);

        ContactEnquiry::create($validated);

        return back()->with('status', 'Thank you — your message has reached the registry. We reply to enquiries within two working days.');
    }

    /** @return array<int, array{name: string, email: string, phone: string, handles: string}> */
    private function offices(): array
    {
        return [
            ['name' => 'Registry (general enquiries)', 'email' => 'info@olodo.edu.ng', 'phone' => '+234 800 000 0000', 'handles' => 'Admissions questions, documents, verification requests'],
            ['name' => 'Admissions Office', 'email' => 'admissions@olodo.edu.ng', 'phone' => '+234 800 000 0001', 'handles' => 'Application status, entry requirements, offer letters'],
            ['name' => 'Bursary (fees & payments)', 'email' => 'finance@olodo.edu.ng', 'phone' => '+234 800 000 0002', 'handles' => 'Tuition invoices, receipts, payment difficulties'],
            ['name' => 'ICT Support', 'email' => 'support@olodo.edu.ng', 'phone' => '+234 800 000 0003', 'handles' => 'Portal access, password resets, technical problems'],
        ];
    }
}
