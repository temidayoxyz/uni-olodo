<?php

namespace Database\Seeders;

use App\Enums\AnnouncementScope;
use App\Models\AcademicSession;
use App\Models\Announcement;
use App\Models\AnnouncementAudience;
use App\Models\CampusEvent;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\NewsArticle;
use App\Models\PaymentTransaction;
use App\Models\ResourceCategory;
use App\Models\ResourceItem;
use App\Models\Semester;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampusLifeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@olodo.edu.ng')->first();
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->first();
        $zainab = User::where('email', 'z.adeyemi@student.olodo.edu.ng')->first();
        $david = User::where('email', 'd.okon@student.olodo.edu.ng')->first();
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->first();
        $csDept = Department::where('code', 'CSC')->first();

        // --- Announcements (audience targeting enforced at read time) -------------
        $registrationNotice = Announcement::create([
            'title' => 'Course registration for '.$this->currentTermName().' closes soon',
            'body' => "Registration for the current semester is open. Log in to your student portal, review your programme requirements, and submit your course registration before the window closes.\n\nLate registration carries a penalty and requires the approval of your academic adviser.",
            'priority' => 'high',
            'pinned' => true,
            'author_id' => $registrar->id,
            'published_at' => now()->subDays(10),
            'expires_at' => now()->addWeeks(6),
        ]);
        AnnouncementAudience::create(['announcement_id' => $registrationNotice->id, 'scope' => AnnouncementScope::University->value]);

        $seminar = Announcement::create([
            'title' => 'CS departmental seminar: building for low-bandwidth users',
            'body' => "The Department of Computer Science continues its seminar series with a session on designing applications that work well on Nigerian mobile networks.\n\nVenue: LT1 · Time: 2:00 pm this Friday.\nAll CS students are encouraged to attend; 300-level students above all — the topic connects directly to CSC 308 coursework.",
            'priority' => 'normal',
            'author_id' => $obi->id,
            'published_at' => now()->subDays(3),
        ]);
        AnnouncementAudience::create(['announcement_id' => $seminar->id, 'scope' => AnnouncementScope::Department->value, 'scope_id' => $csDept->id]);

        $labMove = Announcement::create([
            'title' => 'CSC 305 lab session moved to Lab 2B',
            'body' => "This week's database laboratory holds in Lab 2B instead of Lab 1A. Come with your clinic dataset from module one; we will run queries against it live.",
            'priority' => 'normal',
            'author_id' => $obi->id,
            'published_at' => now()->subDay(),
        ]);
        AnnouncementAudience::create([
            'announcement_id' => $labMove->id,
            'scope' => AnnouncementScope::Offering->value,
            'scope_id' => CourseOffering::query()
                ->whereHas('course', fn ($q) => $q->where('code', 'CSC 305'))
                ->where('semester_id', Semester::where('is_current', true)->value('id'))
                ->value('id'),
        ]);

        $staffNotice = Announcement::create([
            'title' => 'Provisional results upload deadline',
            'body' => 'All course lecturers should complete component-score entry for concluded offerings before the senate results committee meets. Contact the examinations unit for the moderation template.',
            'priority' => 'high',
            'author_id' => $registrar->id,
            'published_at' => now()->subDays(5),
        ]);
        AnnouncementAudience::create([
            'announcement_id' => $staffNotice->id,
            'scope' => AnnouncementScope::Role->value,
            'scope_id' => 'staff',
        ]);

        // --- News -------------------------------------------------------------------
        $news = [
            ['Vice-Chancellor commissions the new digital innovation studio',
                'The studio gives computing students dedicated space for capstone projects, with workstations, a small server rack, and booking-first access rules.',
                'research', now()->subDays(12)],
            ['University of Olodo admits its largest cohort yet — carefully',
                'The admissions office describes how a growing intake is being matched with deliberate investment in teaching staff rather than lecture-hall crowding.',
                'community', now()->subDays(26)],
            ['Accounting students place second at the national case competition',
                'Our four-student team reached the final round of the national accounting case competition, finishing second of forty-two institutions.',
                'news', now()->subDays(40)],
            ['Public lecture: statistics in public health decision-making',
                'The Faculty of Natural & Applied Sciences hosts a public lecture on how statistical evidence shapes health policy, open to staff, students, and visitors.',
                'research', now()->subDays(55)],
        ];

        foreach ($news as [$title, $excerpt, $category, $publishedAt]) {
            NewsArticle::create([
                'author_id' => $admin->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $excerpt,
                'body' => $this->articleBody($title),
                'category' => $category,
                'published_at' => $publishedAt,
            ]);
        }

        // --- Events -----------------------------------------------------------------
        $events = [
            ['Orientation for new students', 'orientation', now()->addWeeks(2)->next('Monday')->setTime(9, 0), 'Main Auditorium'],
            ['Public lecture: AI and the future of Nigerian agriculture', 'public_lecture', now()->addWeeks(3)->setTime(14, 0), 'Faculty of Science Lecture Theatre'],
            ['Career fair 2026', 'career_fair', now()->addWeeks(5)->setTime(10, 0), 'Sports Complex'],
        ];

        foreach ($events as [$title, $category, $startsAt, $location]) {
            CampusEvent::create([
                'created_by' => $admin->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $this->eventDescription($title),
                'location' => $location,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHours(4),
                'category' => $category,
            ]);
        }

        // --- Resource hub -------------------------------------------------------------
        $guides = ResourceCategory::create(['name' => 'Academic Guides', 'slug' => 'academic-guides', 'description' => 'How academic processes work, step by step.', 'position' => 1]);
        $policies = ResourceCategory::create(['name' => 'Policies & Regulations', 'slug' => 'policies', 'description' => 'Governing documents of academic life.', 'position' => 2]);
        $forms = ResourceCategory::create(['name' => 'Forms', 'slug' => 'forms', 'description' => 'Request forms used across the university.', 'position' => 3]);
        $itHelp = ResourceCategory::create(['name' => 'IT Help', 'slug' => 'it-help', 'description' => 'Guides for university systems and services.', 'position' => 4]);

        $items = [
            [$guides, 'How course registration works', 'public', 'link', null, 'https://olodo.edu.ng/guides/course-registration', 'From eligibility checks to adviser approval — what happens at each step of registering for a semester.'],
            [$guides, 'Understanding your transcript', 'students', 'file', $this->seedFile('resources/understanding-your-transcript.pdf'), null, 'What each column means, how GPA and CGPA are computed, and who to ask when something looks wrong.'],
            [$policies, 'Examination malpolicy — examination misconduct regulations', 'public', 'file', $this->seedFile('resources/exam-misconduct-regulations.pdf'), null, 'Definitions, procedures, and sanctions governing examination conduct. Read before your first semester examination.'],
            [$policies, 'Student handbook', 'students', 'file', $this->seedFile('resources/student-handbook.pdf'), null, 'The consolidated handbook: rights, responsibilities, accommodation, discipline, and support services.'],
            [$policies, 'Staff travel & conference policy', 'staff', 'link', null, 'https://olodo.edu.ng/policies/travel', 'Approval workflow and reimbursement rules for conference travel.'],
            [$forms, 'Add / drop form', 'students', 'file', $this->seedFile('resources/add-drop-form.pdf'), null, 'Use after registration closes only with your dean\'s endorsement.'],
            [$forms, 'Transcript request form', 'students', 'file', $this->seedFile('resources/transcript-request-form.pdf'), null, 'Official transcripts are issued by the registry within ten working days of a completed request.'],
            [$itHelp, 'Connecting to campus Wi-Fi', 'public', 'link', null, 'https://olodo.edu.ng/it/wifi', 'Step-by-step setup for laptops and phones on the OLODO-Secure network.'],
            [$itHelp, 'Resetting your portal password', 'public', 'file', $this->seedFile('resources/password-reset-guide.pdf'), null, 'Self-service reset, and what to do when you no longer have access to your email.'],
            [$itHelp, 'LMS quickstart for lecturers', 'staff', 'file', $this->seedFile('resources/lms-quickstart-lecturers.pdf'), null, 'Publishing modules, creating assignments, and opening a quiz in under ten minutes.'],
        ];

        foreach ($items as $i => [$category, $title, $visibility, $type, $filePath, $url, $description]) {
            ResourceItem::create([
                'resource_category_id' => $category->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $description,
                'type' => $type,
                'file_path' => $filePath,
                'external_url' => $url,
                'mime_type' => $filePath !== null ? 'application/pdf' : null,
                'size_bytes' => $filePath !== null ? Storage::disk('local')->size($filePath) : null,
                'visibility' => $visibility,
                'uploaded_by' => $i % 2 === 0 ? $admin->id : $registrar->id,
                'published_at' => now()->subDays(random_int(5, 60)),
                'download_count' => random_int(0, 400),
            ]);
        }

        // --- Invoices -------------------------------------------------------------------
        $fcsTuition = 42_000_000; // ₦420,000.00
        $busTuition = 35_000_000;

        $zainabInvoice = Invoice::create([
            'user_id' => $zainab->id,
            'number' => 'INV-'.date('y').'-10001',
            'type' => 'tuition',
            'title' => 'Tuition — '.$this->currentTermName().' (First instalment)',
            'academic_session_id' => AcademicSession::current()?->id,
            'amount_due' => $fcsTuition,
            'due_at' => now()->addWeeks(8),
            'status' => 'unpaid',
        ]);
        $zainabInvoice->items()->create(['description' => 'Semester tuition — B.Sc. Computer Science (300L)', 'quantity' => 1, 'unit_amount' => $fcsTuition]);

        foreach ([[$david, 2, $busTuition]] as [$student, $seqNo, $amount]) {
            $invoice = Invoice::create([
                'user_id' => $student->id,
                'number' => 'INV-'.date('y').'-100'.$seqNo,
                'type' => 'tuition',
                'title' => 'Tuition — '.$this->currentTermName().' (First instalment)',
                'academic_session_id' => AcademicSession::current()?->id,
                'amount_due' => $amount,
                'due_at' => now()->addWeeks(8),
                'status' => 'paid',
                'paid_at' => now()->subDays(9),
            ]);
            $invoice->items()->create(['description' => 'Semester tuition (first instalment)', 'quantity' => 1, 'unit_amount' => $amount]);
            PaymentTransaction::create([
                'invoice_id' => $invoice->id,
                'reference' => 'UOPAY-'.strtoupper(Str::random(12)),
                'provider' => 'dev',
                'provider_reference' => 'DEV-CHARGE-'.random_int(100000, 999999),
                'amount' => $amount,
                'status' => 'verified',
                'verified_at' => $invoice->paid_at,
                'verified_by' => User::where('role', 'finance_officer')->value('id'),
            ]);
        }

        // --- Support tickets --------------------------------------------------------------
        $support = User::where('role', 'support_staff')->firstOrFail();

        $ticket = SupportTicket::create([
            'user_id' => $zainab->id,
            'category' => 'it',
            'subject' => 'Cannot download PDFs from the resource hub on phone',
            'status' => 'open',
            'assigned_to' => $support->id,
        ]);
        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => $zainab->id,
            'body' => "Good afternoon. When I tap any PDF under Academic Guides on my phone, the download starts but never finishes. It works fine on the library computers.\n\nPhone: Tecno Spark, Chrome browser.",
        ]);
        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => $support->id,
            'body' => 'Thank you for the detail. We have reproduced it on the same device type — it looks like a timeout on large files over mobile data. Please try again on Wi-Fi while we work on a fix; we will update this ticket by Friday.',
        ]);

        $closedTicket = SupportTicket::create([
            'user_id' => $david->id,
            'category' => 'finance',
            'subject' => 'Payment receipt not showing after bank transfer',
            'status' => 'closed',
            'assigned_to' => $support->id,
            'resolved_at' => now()->subDays(6),
        ]);
        SupportMessage::create([
            'support_ticket_id' => $closedTicket->id,
            'sender_id' => $david->id,
            'body' => 'I paid my tuition through a bank transfer two days ago but the portal still shows the invoice unpaid.',
        ]);
        SupportMessage::create([
            'support_ticket_id' => $closedTicket->id,
            'sender_id' => $support->id,
            'body' => 'Your teller has been confirmed and the invoice marked paid. Bank transfers post within one working day; if this happens again, include the teller number in your first message and we will fast-track it.',
        ]);

        // --- Notifications (unread mix per persona) -----------------------------------------
        DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'assignment_graded',
            'notifiable_type' => User::class,
            'notifiable_id' => $zainab->id,
            'data' => [
                'title' => 'Assignment graded — ER Diagram Case Study (CSC 305)',
                'body' => 'Dr. Obi returned your submission with feedback. Score: 84/100.',
                'url' => '/student/courses',
            ],
        ]);
        DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'announcement',
            'notifiable_type' => User::class,
            'notifiable_id' => $zainab->id,
            'data' => [
                'title' => $registrationNotice->title,
                'body' => 'Registration closes in five weeks. Check your credit load against programme requirements.',
                'url' => '/student/announcements',
            ],
        ]);
        DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'invoice',
            'notifiable_type' => User::class,
            'notifiable_id' => $zainab->id,
            'data' => [
                'title' => 'Tuition invoice due',
                'body' => '₦420,000.00 is due by '.$zainabInvoice->due_at?->format('j M Y').'. Payment can be made from the Payments page.',
                'url' => '/student/payments',
            ],
        ]);
        DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'grading_queue',
            'notifiable_type' => User::class,
            'notifiable_id' => $obi->id,
            'data' => [
                'title' => 'Submissions awaiting grading',
                'body' => 'Linked List Library Report (CSC 301): several submissions are waiting for scores.',
                'url' => '/lecturer/courses',
            ],
        ]);
    }

    private function currentTermName(): string
    {
        return AcademicSession::current()?->name ?? date('Y');
    }

    private function seedFile(string $path): string
    {
        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, "%PDF-1.4\n% Seeded placeholder document.\n");
        }

        return $path;
    }

    private function articleBody(string $title): string
    {
        return "# {$title}\n\n"
            ."The university community gathered as details of the initiative were set out by the management team. Speaking at the event, officials emphasised that growth at University of Olodo will be deliberate: every expansion of intake is paired with investment in teaching capacity, learning spaces, and student support.\n\n"
            ."\"We measure ourselves by outcomes, not optics,\" one member of faculty noted. \"Students here are taught in groups where their names are known and their progress is tracked.\"\n\n"
            ."Further announcements will be published on this page as programmes roll out. Students and staff with questions are invited to contact the relevant offices through the support channels listed on the portal.\n";
    }

    private function eventDescription(string $title): string
    {
        return str_contains($title, 'Orientation')
            ? 'Welcome to University of Olodo. Meet your faculty, tour the facilities, complete your enrolment checklist, and get your questions answered before lectures begin. Attendance is compulsory for newly admitted students.'
            : (str_contains($title, 'Career fair')
                ? 'Employers from technology, finance, manufacturing, and the public sector will be on campus. Bring printed copies of your CV; the career services desk runs same-day CV reviews.'
                : 'A public lecture exploring how emerging technologies intersect with practice in Nigeria. Open to students, staff, alumni, and members of the public.');
    }
}
