<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\CourseContent;
use App\Models\CourseModule;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * LMS content for the offerings Dr. Obi owns (CSC 301, CSC 305) plus a lighter
 * set elsewhere: modules, lessons, materials, assignments and quizzes.
 */
class LearningSeeder extends Seeder
{
    public function run(): void
    {
        $semester = Semester::where('is_current', true)->firstOrFail();

        $csc301 = CourseOffering::query()
            ->where('semester_id', $semester->id)
            ->whereHas('course', fn ($q) => $q->where('code', 'CSC 301'))
            ->firstOrFail();
        $csc305 = CourseOffering::query()
            ->where('semester_id', $semester->id)
            ->whereHas('course', fn ($q) => $q->where('code', 'CSC 305'))
            ->firstOrFail();

        // --- CSC 301 — Data Structures & Algorithms ------------------------------
        $m1 = $this->module($csc301, 'Orientation & Complexity Basics', 1, true, 'How the course runs, how you will be assessed, and what "efficient" actually means.');

        $this->lesson($m1, 1, 'text', 'How this course runs', <<<'MD'
## Welcome to CSC 301

Data structures and algorithms are the difference between code that *works* and code that *scales*. This course is demanding but fair: every assessment mirrors the weekly practice.

**Weekly rhythm**
- Two lectures (Monday & Wednesday, LT1) — theory and worked examples.
- A lab task most weeks, submitted through this portal.
- Continuous assessment is worth 40% of your final grade; the examination is 60%.

**What I expect from you:** attempt every lab before the deadline, even imperfectly. Partial honest work teaches more than perfect copied work — and the university's malpractice policy applies to both of us here.
MD);
        $this->lesson($m1, 2, 'file', 'CSC 301 Course Outline', $this->materialFile('csc301/course-outline.pdf', 'CSC-301-Course-Outline.pdf'));
        $this->lesson($m1, 3, 'link', 'Big-O cheat sheet', 'https://www.bigocheatsheet.com/');

        $m2 = $this->module($csc301, 'Lists, Stacks & Queues', 2, true, 'Linear structures, their invariants, and when each one earns its place.');

        $this->lesson($m2, 1, 'text', 'Singly & doubly linked lists', <<<'MD'
## Linked lists

A linked list trades random access for cheap insertion. Remember the three questions for any structure you meet:

1. What does **each operation cost**, worst case?
2. What **invariant** must hold after every operation?
3. Where does the structure **waste memory**?

The lab asks you to implement a doubly linked list with an iterator, then defend your sentinel-node choice in a short report.
MD);
        $this->lesson($m2, 2, 'file', 'Lecture slides — Week 3', $this->materialFile('csc301/week3-slides.pdf', 'CSC301-W3-Linked-Structures.pdf'));

        $m3 = $this->module($csc301, 'Trees & Graphs', 3, false, 'Hierarchies, traversals, shortest paths. Publishes after the Week 5 quiz.');

        $this->lesson($m3, 1, 'text', 'Binary search trees — draft notes', 'Working notes. Will be polished and published after the Week 5 session.');

        // --- CSC 305 — Database Systems I ----------------------------------------
        $dbM1 = $this->module($csc305, 'Relational Foundations', 1, true, 'The relational model, keys, and why integrity constraints exist.');

        $this->lesson($dbM1, 1, 'text', 'Relations, tuples and keys', <<<'MD'
## The relational model in one page

A relation is a **set** of tuples — no duplicates, no order. Everything else (keys, constraints, normalisation) follows from taking that seriously.

For the lab you will design a small schema for a clinic booking system, declare its primary and foreign keys, and explain each constraint in one sentence.
MD);
        $this->lesson($dbM1, 2, 'file', 'Lab dataset — clinic sample data', $this->materialFile('csc305/clinic-sample.sql', 'clinic-sample-data.sql'));

        $dbM2 = $this->module($csc305, 'SQL in Practice', 2, true, 'Joins, aggregation, and reading a query plan before trusting it.');

        $this->lesson($dbM2, 1, 'text', 'Joins without fear', 'Inner, left, right, full — with worked examples against the clinic dataset.');
        $this->lesson($dbM2, 2, 'video', 'Query plans walkthrough', 'https://www.youtube.com/watch?v=example-csc305');

        // --- Assignments -----------------------------------------------------------
        // Graded + returned (Zainab has feedback).
        $erAssignment = $csc305->assignments()->create([
            'title' => 'ER Diagram Case Study — Olodo Clinic',
            'instructions' => "Model the Olodo Clinic booking scenario from the lab dataset.\n\nDeliverables:\n1. An ER diagram (image or PDF) showing entities, attributes and relationships.\n2. A short rationale (max 300 words) for two non-obvious design decisions.\n\nSubmit as a single PDF.",
            'points' => 100,
            'available_from' => now()->subDays(24),
            'due_at' => now()->subDays(12),
            'published_at' => now()->subDays(24),
        ]);

        // Pending grading queue for Dr. Obi; Zainab has already submitted.
        $reportAssignment = $csc301->assignments()->create([
            'title' => 'Linked List Library Report',
            'instructions' => "Implement a doubly linked list with an iterator in the language of your choice, then write a report:\n\n1. Your sentinel-node decision and why.\n2. Complexity table for insert / delete / search.\n3. One test case that exposed a real bug during development.\n\nSubmit your code as a .zip and the report as PDF. Late submissions close 72 hours after the deadline at a penalty stated in the outline.",
            'points' => 100,
            'available_from' => now()->subDays(10),
            'due_at' => now()->addDays(7),
            'late_until' => now()->addDays(10),
            'published_at' => now()->subDays(10),
        ]);

        $this->seedSubmissions($erAssignment, graded: true);
        $this->seedSubmissions($reportAssignment, graded: false);

        // --- Quiz ------------------------------------------------------------------
        $quiz = $csc301->quizzes()->create([
            'title' => 'Queues & Deques Check',
            'instructions' => 'Five quick questions on linear structures. 20 minutes, one attempt. Results are shown immediately after submission.',
            'duration_minutes' => 20,
            'available_from' => now()->addDays(2),
            'available_until' => now()->addDays(9),
            'max_attempts' => 1,
            'shuffle_questions' => true,
            'reveal_answers' => true,
            'published_at' => now(),
        ]);

        $questions = [
            ['single_choice', 'Which queue discipline serves elements in arrival order?', [
                ['key' => 'a', 'text' => 'LIFO'], ['key' => 'b', 'text' => 'FIFO'],
                ['key' => 'c', 'text' => 'Priority order'], ['key' => 'd', 'text' => 'Random order'],
            ], ['b']],
            ['true_false', 'A deque allows insertion and removal at both ends.', null, ['true']],
            ['multi_choice', 'Which operations are O(1) amortised for a well-implemented circular-array queue? (Select all)', [
                ['key' => 'a', 'text' => 'enqueue'], ['key' => 'b', 'text' => 'dequeue'],
                ['key' => 'c', 'text' => 'search by value'], ['key' => 'd', 'text' => 'peek front'],
            ], ['a', 'b', 'd']],
            ['short_answer', 'Name the classic error where a linear-array queue exhausts free space at one end while slots remain at the other.', null, ['false overflow', 'falseoverflow']],
            ['single_choice', 'A priority queue implemented with an unsorted array has which dequeue cost?', [
                ['key' => 'a', 'text' => 'O(1)'], ['key' => 'b', 'text' => 'O(log n)'],
                ['key' => 'c', 'text' => 'O(n)'], ['key' => 'd', 'text' => 'O(n log n)'],
            ], ['c']],
        ];

        foreach ($questions as $i => [$type, $prompt, $options, $answers]) {
            $quiz->questions()->create([
                'type' => $type,
                'prompt' => $prompt,
                'options' => $options ? collect($options)->map(fn ($o) => ['key' => $o['key'], 'text' => $o['text']])->all() : null,
                'answers' => $answers,
                'points' => 2,
                'position' => $i + 1,
            ]);
        }
    }

    private function module(CourseOffering $offering, string $title, int $position, bool $published, string $summary): CourseModule
    {
        return $offering->modules()->create([
            'title' => $title,
            'summary' => $summary,
            'position' => $position,
            'published_at' => $published ? now()->subWeeks(4 - min($position, 3)) : null,
        ]);
    }

    private function lesson(CourseModule $module, int $position, string $type, string $title, string $payload): CourseContent
    {
        return $module->contents()->create([
            'type' => $type,
            'title' => $title,
            ...match ($type) {
                'text' => ['body' => $payload],
                'file' => ['file_path' => $payload, 'file_name' => basename($payload)],
                default => ['external_url' => $payload],
            },
            'position' => $position,
            'published_at' => now()->subWeeks(3),
        ]);
    }

    /** Store a small placeholder file and return its storage path. */
    private function materialFile(string $path, string $name): string
    {
        if (! Storage::disk('local')->exists($path)) {
            if (str_ends_with($path, '.pdf')) {
                Storage::disk('local')->put($path, $this->placeholderPdf("University of Olodo — {$name}"));
            } else {
                Storage::disk('local')->put($path, "-- Sample teaching material seeded for demo purposes.\n");
            }
        }

        return $path;
    }

    private function placeholderPdf(string $title): string
    {
        $text = str_replace(['(', ')', '\\'], '', $title);

        $stream = "BT /F1 18 Tf 60 740 Td ({$text}) Tj ET";

        $objects = [
            '1 0 obj' => '<< /Type /Catalog /Pages 2 0 R >>',
            '2 0 obj' => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '3 0 obj' => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '4 0 obj' => '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '5 0 obj' => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $head => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= "{$head}\n{$body}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= 'xref\n0 '.(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >> startxref '.$xrefPos.' %%EOF';

        return $pdf;
    }

    /**
     * Seed submissions from enrolled students. When $graded, Dr. Obi has scored
     * everything (Zainab gets specific feedback); otherwise all are pending.
     */
    private function seedSubmissions(Assignment $assignment, bool $graded): void
    {
        $students = User::query()
            ->where('role', 'student')
            ->with('studentProfile')
            ->whereHas('registrations.items', fn ($q) => $q
                ->where('course_offering_id', $assignment->course_offering_id)
                ->where('status', 'registered')
                ->whereHas('registration', fn ($r) => $r->where('status', 'approved')))
            ->orderBy('email')
            ->take(7)
            ->get();

        $scores = [84, 71, 66, 58, 92, 77, 63];

        foreach ($students as $i => $student) {
            $submittedAt = $assignment->due_at->copy()->subDays(random_int(1, 4));
            $fileName = strtolower(str_replace(' ', '-', $student->studentProfile->matric_number ?? 'student')).'-'.str_replace([' ', '/'], '-', strtolower($assignment->title)).'.pdf';

            $submission = $assignment->submissions()->create([
                'student_id' => $student->id,
                'file_path' => "submissions/{$assignment->id}/".$fileName,
                'original_name' => $fileName,
                'mime_type' => 'application/pdf',
                'size_bytes' => random_int(120_000, 900_000),
                'note' => $i % 3 === 0 ? 'Dr. Obi, please note my diagram uses crow\'s foot notation throughout.' : null,
                'submitted_at' => $submittedAt,
            ]);

            Storage::disk('local')->put($submission->file_path, $this->placeholderPdf("Submission — {$assignment->title}"));

            if ($graded) {
                $score = $scores[$i % count($scores)];
                $submission->forceFill([
                    'score' => $score,
                    'feedback' => $student->email === 'z.adeyemi@student.olodo.edu.ng'
                        ? 'Excellent diagram clarity, Zainab. Two points to tighten: your Patient–Appointment relationship needed cardinality annotations, and the billing entity should reference the appointment, not the patient. See the rubric row C2.'
                        : 'Solid work overall. Check the rubric comments annotated in your returned script during office hours.',
                    'graded_by' => $assignment->offering->lecturer_id,
                    'graded_at' => now()->subDays(9),
                ])->save();
            }
        }
    }
}
