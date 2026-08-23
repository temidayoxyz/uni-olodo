<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Provisional component scores kept by the lecturer (never shown as official).
        Schema::create('course_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('ca_score', 5, 2)->nullable();  // continuous assessment, out of 40
            $table->decimal('exam_score', 5, 2)->nullable(); // examination, out of 60
            $table->text('note')->nullable();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_offering_id', 'student_id']);
        });

        // Offering-level approval workflow: submitted → approved → published (or returned).
        Schema::create('result_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 16)->default('submitted'); // submitted | approved | published | returned
            $table->text('note')->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('course_offering_id');
        });

        // Immutable snapshot released to students. Official results come ONLY from here.
        Schema::create('published_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_offering_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('ca_score', 5, 2)->nullable();
            $table->decimal('exam_score', 5, 2)->nullable();
            $table->decimal('total', 5, 2);
            $table->string('grade_letter', 2);
            $table->decimal('grade_point', 3, 1);
            $table->boolean('is_passed');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['course_offering_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('published_results');
        Schema::dropIfExists('result_submissions');
        Schema::dropIfExists('course_scores');
    }
};
