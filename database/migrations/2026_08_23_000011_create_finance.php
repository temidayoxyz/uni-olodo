<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Amounts are stored as integer minor units (kobo). ₦350,000.00 = 35_000_000.
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number', 24)->unique();          // INV-2026-000123
            $table->string('type', 24)->default('tuition');  // application_fee | tuition | other
            $table->string('title');
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_due');
            $table->timestamp('due_at')->nullable();
            $table->string('status', 16)->default('unpaid'); // unpaid | paid | void
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount');
        });

        // Success is only ever trusted after server-side verification — never a redirect alone.
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();           // UOPAY-XXXXXXXXXXXX
            $table->string('provider', 24)->default('manual'); // manual | dev | paystack
            $table->string('provider_reference')->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 16)->default('initiated'); // initiated | pending | verified | failed | refunded
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
