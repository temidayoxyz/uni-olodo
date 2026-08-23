# University of Olodo — Technical Architecture

> Companion to [PRODUCT.md](PRODUCT.md).

## 1. Stack & rationale

| Layer | Choice | Why |
| --- | --- | --- |
| Framework | Laravel (latest stable), PHP 8.4 | Full-stack foundation; batteries included (auth, notifications, queues, storage, policies) |
| Views | Blade + reusable Blade components | Server-rendered pages; authorization and validation stay server-authoritative by construction |
| CSS | Tailwind CSS v4 with design-token CSS variables | Token-driven design system; no runtime JS styling |
| JS | Alpine.js (minimal) | Dropdowns, mobile nav, small client niceties. No SPA, no build-time framework |
| Livewire | **Not adopted (v1)** | Every interaction in scope is form-driven; POST/redirect/re-render gives identical UX with fewer moving parts. All state already lives server-side, so per-screen adoption later needs no rearchitecture |
| Database | SQLite (dev/demo), portable schema | Required for zero-config development; no SQLite-specific SQL in app code; migrations remain MySQL/Postgres-compatible |
| Assets | Vite | Standard Laravel asset pipeline |
| Icons | blade-lucide-icons | One coherent stroke icon system as Blade components |
| Fonts | Self-hosted via @fontsource (Fraunces + Public Sans) | No external font CDN dependency at runtime |

## 2. Code organisation

Conventional Laravel with light domain discipline — no premature micro-services:

```
app/
  Enums/            Role, ApplicationStatus, ResourceVisibility, …
  Models/           Eloquent models + relationships
  Policies/         Resource authorization
  Http/
    Controllers/    Area-prefixed: Public\, Applicant\, Student\, Lecturer\, Admin\
    Requests/       Form Requests (validation lives here, not in controllers)
  Services/         Workflow services (RegistrationService, ResultService, ApplicationService, Audit)
  Support/          Small helpers (GpaCalculator, Settings)
```

Business workflows with rules (registration validation, application state machine, result approval) live in `app/Services/*` — controllers orchestrate, services decide, policies authorize.

## 3. Data model (summary)

**Identity:** `users` (role enum column, status), Laravel sessions/resets.

**Academic structure:** `faculties` → `departments` → `programmes` → `courses` (catalogue) → `course_prerequisites`; `academic_sessions` → `semesters` (with registration window columns); `course_offerings` (course × semester, lecturer, capacity, status) → `offering_schedules` (weekday/time/venue rows).

**Students:** `student_profiles` (matric no, programme, level, adviser, status); `registrations` (student × semester, draft/submitted/approved) → `registration_items` (offering, registered/dropped).

**Admissions:** `applications` (personal + education data, state machine columns, decision columns) → `application_choices` (ranked programme choices) → `application_documents` (typed uploads with verification status).

**Learning:** `course_modules` → `course_contents` (typed: text/file/link/video); `assignments` → `assignment_submissions` (attempted, late-aware, score/feedback); `quizzes` → `quiz_questions` (typed, options/answers as JSON) → `quiz_attempts` → `quiz_answers`.

**Results:** `course_scores` (CA/exam per student per offering — provisional layer); `result_submissions` (offering-level approval workflow); `published_results` (immutable snapshot per student per offering, released to students only from here).

**Communication:** `announcements` → `announcement_audiences` (scope_type + scope_id: university/role/faculty/department/programme/offering — enforced server-side at read time); Laravel database notifications; `news_articles`, `events`, `contact_enquiries`.

**Resources:** `resource_categories` → `resources` (visibility: public/students/staff; file or link).

**Finance:** `invoices` → `invoice_items`; `payment_transactions` (provider abstraction, verification-gated success).

**Support & audit:** `support_tickets` → `support_messages`; `audit_logs` (actor, action, subject, properties); `settings` (key/value).

Integrity rules of note: unique (student, offering) across active registrations; unique application choice rank; unique matric numbers; FKs with proper cascades; state transitions validated in services; money stored as integer minor units; all timestamps UTC-backed by Laravel.

## 4. Authorization matrix (enforced in Policies/Gates)

| Capability | Who |
| --- | --- |
| View own application / withdraw draft | Application owner only |
| Review/decide applications | `admissions_officer`, `super_admin` |
| View own enrolments, submissions, results | Owning student only |
| Register courses | Active students, inside registration window (service-enforced) |
| View offering (LMS) | Enrolled students + assigned lecturer + academic admins |
| Manage offering content/assessments | Assigned lecturer (+ `registrar`/`super_admin` override) |
| Grade submissions for an offering | Assigned lecturer only — never cross-offering |
| Submit provisional results | Assigned lecturer |
| Approve/publish results | `registrar`, `super_admin` |
| Manage users/roles | `super_admin` (role changes audited) |
| Manage academic structure | `registrar`, `super_admin` |
| Manage announcements/resources/news | `super_admin`, scoped staff roles |

`Gate::before` grants `super_admin` everything; every other role passes through explicit policies. UI hides what a role cannot do, but the server is the boundary.

## 5. Routing architecture

```
/                          public site (separate editorial shell)
/about /academics /admissions /campus-life /research /news /events /resources /contact
/login /register /forgot-password /email/verify…

/applicant/…               applicant area (application wizard, status)
/student/…                 student portal
/lecturer/…                lecturer workspace
/courses/{offering}/…      shared LMS space (student view / manage view by policy)
/admin/…                   administration (role-scoped)
/settings/…                shared profile & security
```

Middleware: `auth`, verified (sensitive areas), role middleware per group; route model binding with scoped policies (403s, never leaked 404s for existing-but-forbidden records where distinguishable).

## 6. Design system (summary)

- **Palette:** warm paper background, deep pine-green institutional primary, ochre accent used sparingly, ink text; full semantic set (success/warning/danger/info) + focus ring tokens. Defined once as CSS variables; Tailwind maps to them.
- **Type:** Fraunces (display/editorial, public site) + Public Sans (UI/body everywhere); tabular numerals for academic data; strict type scale.
- **Spacing:** 4/8/12/16/24/32/48/64/96 rhythm.
- **Shape:** controls 6–8px, surfaces 10–14px, media contextual; nothing pill-shaped by default.
- **Depth:** borders first; elevation only for overlays and genuinely floating UI.
- **Motion:** 150–250ms ease-out state changes and reveals; `prefers-reduced-motion` respected globally.
- **Public vs portal:** public pages are editorial (serif display, generous whitespace, asymmetric grids); portal is dense and calm (sans, tables, lists, persistent context).

## 7. Testing strategy

- **Feature:** auth + verification; role access matrix; application submission & state transitions; registration rule engine (prerequisites, load cap, conflicts, window); assignment submission (deadline, late, resubmission); grading permission scoping; result publication visibility; announcement audience targeting.
- **Unit:** prerequisite validation, GPA/grade-point calculation, application state machine, quiz scoring.
- Factories + one orchestrated `DatabaseSeeder` produce a believable world; tests use targeted factories.

## 8. Security posture

CSRF everywhere (Blade forms), mass-assignment protection (strict fillable + form requests), Eloquent binding (no raw SQL), file uploads validated by type/size and stored with random names outside public root, private files served through authorized controller responses, login rate limiting, audited privileged actions, secrets only via `.env`, no client-trusted state (payments verify server-side; results publish only from approved snapshots).

## 9. Performance notes

Eager loading everywhere relationships are rendered; paginated lists; dashboard queries trimmed to current semester scope; public programme/news listings cache-friendly (short TTL remember()); queued mail/notifications via database queue driver in dev.
