<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\SupportStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payments: initiation, gateway verification, atomic settlement, ownership,
 * and the bursary's manual path. No redirect is ever trusted on its own.
 */
class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SupportStaffSeeder::class);
    }

    private function debtor(): array
    {
        $user = User::factory()->role(UserRole::Student)->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'number' => 'INV-T-'.random_int(10000, 99999),
            'type' => 'tuition',
            'title' => 'Tuition — first instalment',
            'amount_due' => 25_200_000, // ₦252,000.00
            'due_at' => now()->addWeeks(4),
            'status' => 'unpaid',
        ]);
        $invoice->items()->create(['description' => 'Semester tuition', 'quantity' => 1, 'unit_amount' => $invoice->amount_due]);

        return [$user, $invoice];
    }

    public function test_paying_opens_a_transaction_and_settles_through_verification(): void
    {
        [$user, $invoice] = $this->debtor();

        $this->actingAs($user)->get('/payments')
            ->assertOk()
            ->assertSee(number_format($invoice->amount_due / 100, 2));

        // Initiate → simulated checkout.
        $this->post("/payments/{$invoice->id}/pay")
            ->assertRedirect();

        $transaction = PaymentTransaction::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame('initiated', $transaction->status);
        $this->assertSame($invoice->amount_due, $transaction->amount);

        $this->get("/payments/checkout/{$transaction->reference}")
            ->assertOk()
            ->assertSee('Simulated');

        // Completing runs server-side verification and settles atomically.
        $this->post("/payments/checkout/{$transaction->reference}/complete")
            ->assertRedirect(route('payments.show', $invoice))
            ->assertSessionHas('status');

        $this->assertSame('verified', $transaction->fresh()->status);
        $this->assertTrue($invoice->fresh()->isPaid());
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.settled']);
    }

    public function test_revisiting_the_checkout_after_settlement_is_idempotent(): void
    {
        [$user, $invoice] = $this->debtor();

        $this->actingAs($user)->post("/payments/{$invoice->id}/pay");
        $reference = PaymentTransaction::latest('id')->value('reference');

        $this->post("/payments/checkout/{$reference}/complete");
        $this->post("/payments/checkout/{$reference}/complete"); // double-submit

        $this->assertSame(1, PaymentTransaction::count());
        $this->assertSame('verified', PaymentTransaction::first()->status);
        $this->assertTrue($invoice->fresh()->isPaid());
    }

    public function test_paid_invoices_refuse_new_payment_attempts(): void
    {
        [$user, $invoice] = $this->debtor();
        $invoice->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $this->actingAs($user)->post("/payments/{$invoice->id}/pay")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, $invoice->transactions()->count());
    }

    public function test_another_users_invoice_is_invisible_and_unpayable(): void
    {
        [, $invoice] = $this->debtor(); // belongs to someone else
        $intruder = User::factory()->role(UserRole::Student)->create();

        $this->actingAs($intruder)->get("/payments/{$invoice->id}")->assertForbidden();
        $this->actingAs($intruder)->post("/payments/{$invoice->id}/pay")->assertForbidden();

        $this->assertSame(0, $invoice->transactions()->count());
    }

    public function test_bursary_verifies_manual_transfers_and_others_cannot(): void
    {
        $finance = User::factory()->role(UserRole::FinanceOfficer)->create();
        $lecturer = User::factory()->role(UserRole::Lecturer)->create();

        [$payer, $invoice] = $this->debtor();

        $manual = PaymentTransaction::create([
            'invoice_id' => $invoice->id,
            'reference' => 'UOPAY-MANUAL-0001',
            'provider' => 'manual',
            'amount' => $invoice->amount_due,
            'status' => 'initiated',
        ]);

        $url = "/admin/payments/transactions/{$manual->id}/verify";

        $this->actingAs($finance)->get('/admin/payments')->assertOk();
        $this->actingAs($lecturer)->get('/admin/payments')->assertForbidden();

        $this->actingAs($lecturer)->post($url)->assertForbidden();

        $this->actingAs($finance)->post($url)
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('verified', $manual->fresh()->status);
        $this->assertTrue($invoice->fresh()->isPaid());
    }
}
