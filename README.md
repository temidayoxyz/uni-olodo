# University of Olodo — Digital Campus Platform

A complete university ecosystem built with **Laravel**: public website, admissions & application lifecycle (wizard → officer review → offer acceptance), student portal with rule-engine course registration and timetable, integrated LMS (modules/materials/assignments/quizzes), the full results lifecycle (scores → registry approval → immutable publication), verified payments, resource hub, and role-scoped administration with audit trails.

> University of Olodo is a fictional institution. All seeded people, programmes and records are sample data that demonstrate real product relationships — not factual claims.

## What's implemented

| Area | Highlights |
| --- | --- |
| Public site | Editorial homepage, academics directory, programme detail pages, admissions overview, news & events, contact with persisted enquiries |
| Admissions | Five-step application wizard with save-progress, document uploads (private storage), state machine enforced in `ApplicationService`, officer queue with document verification and audited decisions, offer acceptance → student activation (matric number + first invoice) |
| Registration | Rule engine: window, level eligibility, prerequisites from **published results only**, 24-credit cap, timetable clash detection; basket UI explains violations inline; registrar approval |
| LMS | Shared `/courses` space; modules/lessons/materials; assignments with replace-before-deadline; per-lecturer grading queues with required feedback; timed quizzes auto-scored server-side (duration + grace, no invented scores) |
| Results | Component scores → submission (complete gradebook required) → approve / return → publish immutable official snapshots; student results page with honest GPA math; print-ready unofficial transcript |
| Payments | Provider abstraction (`PaymentProvider`) with DevGateway simulation; settlement only after server-side verification, atomic and idempotent; bursary manual-transfer verification |
| Administration | Academic structure CRUD, calendar windows, users & roles (self-lockout protected), audit log views across all privileged actions |

## Demo walkthroughs

Sign in as each persona and try:

- **Zainab** (`z.adeyemi@student.olodo.edu.ng`) — dashboard "today", registration basket with live rule checks, CSC 301 materials/assignments/quiz window, official results & CGPA ≈ 3.62, unpaid tuition invoice → pay via simulated gateway
- **Dr. Obi** (`c.obi@olodo.edu.ng`) — grading queue for CSC 301, gradebook → submit provisional results
- **Registrar** (`registrar@olodo.edu.ng`) — approve registrations, approve/publish Dr. Obi's results, move a registration window, browse structure
- **Admissions officer** (`admissions@olodo.edu.ng`) — review Emeka's application, verify documents, decide
- **Super admin** (`admin@olodo.edu.ng`) — users & roles with audit trail

## Requirements

- PHP 8.3+ (extensions: `sqlite3`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `gd`, `intl`)
- Composer
- Node.js 20+ / npm

## Quick start

```bash
composer install
npm install && npm run build

cp .env.example .env          # SQLite is preconfigured as the dev database
php artisan key:generate

php artisan migrate:fresh --seed
php artisan serve             # http://127.0.0.1:8000
```

`touch database/database.sqlite` first if your setup does not create it automatically.

## Demo accounts

All demo users use the password **`password`** (development seed only — never in production).

| Role | Email |
| --- | --- |
| Super Administrator | `admin@olodo.edu.ng` |
| Registrar | `registrar@olodo.edu.ng` |
| Admissions Officer | `admissions@olodo.edu.ng` |
| Finance Officer | `finance@olodo.edu.ng` |
| Lecturer | `c.obi@olodo.edu.ng` |
| Student (primary demo) | `z.adeyemi@student.olodo.edu.ng` |
| Applicant (under review) | `emeka.nwosu@example.com` |

The full persona map lives in [docs/SEED.md](docs/SEED.md).

## Documentation

- [Product definition](docs/PRODUCT.md) — institution, users, flows, scope
- [Architecture](docs/ARCHITECTURE.md) — stack rationale, data model, authorization matrix
- [Seeded world](docs/SEED.md) — demo data specification

## Tests

```bash
php artisan test
```

## Production notes

SQLite powers local development only. The schema avoids SQLite-specific SQL and migrates to MySQL/PostgreSQL unchanged; payment providers are abstracted behind a verification-based interface and ship with a manual/dev provider.
