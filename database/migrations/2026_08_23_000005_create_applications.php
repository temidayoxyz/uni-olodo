<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number', 24)->unique();          // UOA-2026-000123
            $table->foreignId('intake_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();

            // Personal details (snapshot at submission time; user profile is separate)
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names', 128)->nullable();
            $table->date('date_of_birth');
            $table->string('gender', 16);
            $table->string('phone', 24);
            $table->text('address');
            $table->string('nationality', 64)->default('Nigeria');
            $table->string('state_of_origin', 64)->nullable();

            // Educational background
            $table->string('qualification', 32);             // waec | neco | nabteb | equivalent
            $table->unsignedSmallInteger('examination_year');
            $table->string('previous_school', 191)->nullable();
            $table->text('personal_statement')->nullable();

            // State machine: draft → submitted → under_review → …(see ApplicationStatus)
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('offer_responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('application_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rank'); // 1 = first choice
            $table->unique(['application_id', 'rank']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);                      // passport_photograph | olevel_result | entrance_exam_slip | birth_certificate | other
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->string('verification', 16)->default('pending'); // pending | verified | rejected
            $table->text('reviewer_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('application_choices');
        Schema::dropIfExists('applications');
    }
};
