# University of Olodo — Seeded World Specification

> Reference for factories/seeders. All content is fictional sample data that demonstrates real product relationships. Dates below assume build date August 2026; the seeder computes relative to `now` so the demo stays alive over time.

## Timeline anchor

| When | What |
| --- | --- |
| 2024/2025, 2025/2026 | Completed academic sessions with **published** results (gives history, GPA, transcripts) |
| 2026/2027 First Semester | **Current semester**: starts mid-September, ends late January |
| Now → +~5 weeks | **Registration window OPEN** (closes late September) — registration flow fully demoable today |
| Today − 10 days → + 7 days | One published assignment due soon; one graded assignment returned; quiz window opening |

## Institutional structure

```
Faculty of Computing & Information Sciences   (FCS)
└── Department of Computer Science            (CSC)
    ├── B.Sc. Computer Science        (CSC-BS, 8 semesters)
    ├── B.Sc. Software Engineering    (SWE-BS)
    └── B.Sc. Data Science            (DSC-BS)
Faculty of Management Sciences                (FMS)
├── Department of Business Administration     (BUS)
│   └── B.Sc. Business Administration         (BUS-BS)
└── Department of Accounting                  (ACC)
    └── B.Sc. Accounting                      (ACC-BS)
Faculty of Natural & Applied Sciences         (FNS)
└── Department of Mathematical Sciences       (MTH)
    ├── B.Sc. Mathematics                     (MTH-BS)
    └── B.Sc. Statistics                      (STA-BS)
Faculty of Engineering                        (FEG)
└── Department of Electrical & Electronic Engineering (EEE)
    └── B.Eng. Electrical & Electronic Engineering (EEE-BE)
```

Catalogue ≈ 30 courses across these departments with prerequisite chains (e.g. CSC 201 → CSC 202 → CSC 301 Data Structures & Algorithms → CSC 304 Operating Systems I; CSC 305 Database Systems I; CSC 303 Computer Architecture; GST/MTH general studies included for realism).

## Demo accounts (all passwords: `password`)

| Role | Name | Email |
| --- | --- | --- |
| Super Administrator | Amara Okafor | admin@olodo.edu.ng |
| Registrar | Tunde Bakare | registrar@olodo.edu.ng |
| Admissions Officer | Ngozi Eze | admissions@olodo.edu.ng |
| Finance Officer | Sani Garba | finance@olodo.edu.ng |
| Lecturer | Dr. Chiamaka Obi (CS) | c.obi@olodo.edu.ng |
| Lecturer | Mr. Yusuf Ibrahim (BusAdmin) | y.ibrahim@olodo.edu.ng |
| Student (300L CS, primary demo) | Zainab Adeyemi | z.adeyemi@student.olodo.edu.ng |
| Student (200L BusAdmin) | David Okon | d.okon@student.olodo.edu.ng |
| Student (400L CS) | Funmi Alade | f.alade@student.olodo.edu.ng |
| Applicant (under review) | Emeka Nwosu | emeka.nwosu@example.com |
| Applicant (draft) | Fatima Bello | fatima.bello@example.com |

Plus ~9 additional students across levels/programmes via factory so tables, queues and gradebooks have population.

## State highlights each persona must demonstrate

**Zainab (student):**
- Registered & approved for 5 offerings (~17 credits) this semester incl. CSC 301 (Dr. Obi)
- Dashboard: next class today, assignment due in days, quiz opening, fee alert (unpaid tuition invoice), registration-open notice
- Full 2024/2025 + 2025/2026 history with grades → CGPA ~3.6; transcript viewable
- One graded+returned assignment with feedback; one pending submission

**Dr. Obi (lecturer):**
- Owns CSC 301 & CSC 305 offerings with modules/lessons/materials published
- Grading queue: several submissions awaiting scores; one released quiz auto-graded
- Can submit provisional results for a completed past offering → approval chain demo

**Registrar:** pending result-submission approvals; academic structure management; registration windows configurable.

**Admissions officer:** queue with Emeka (under review, documents pending verification), Aisha Musa (awaiting_documents → more_info_required sent), Chidi Anyanwu (accepted — shows decision letters + offer acceptance), Fatima (applicant-side draft).

**Applicants:** Emeka sees "under review + verified/pending docs"; Fatima resumes her draft wizard mid-way.

## Content inventory

- Announcements: university-wide (registration open, high priority), CS-department scoped, CSC 301 offering scoped, staff-only example
- Resources: 4 categories (Academic Guides, Policies, Forms, IT Help) × ~10 items mixing public/student/staff visibility, file & link types
- News: 4 articles; Events: 3 upcoming (orientation, public lecture, career fair)
- Invoices: application fees (paid, verified) for applicants; tuition invoices for students (one unpaid → payment flow demo)
- Support: one open IT ticket (student) with reply thread; one closed
- Notifications: unread mix per user (assignment feedback, announcement, admission status, invoice)

## Data quality rules

- Names, matric numbers (`UO/CSC/21/0142` pattern), phone numbers, venues (LT1, Lab 2B), and emails stay internally consistent.
- Scores obey component weights (CA 40 / Exam 60); grade points derive from total honestly; GPA math checks out.
- Every list screen has ≥1 row; every empty-state scenario is reachable deliberately (e.g. a fresh student without registrations is NOT seeded — empty states come from filtered views/tests instead).
