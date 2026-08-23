# University of Olodo — Product Definition

> Living document. Updated as product decisions are made.

## 1. The Institution

**University of Olodo** is a fictional comprehensive university located in Olodo, Ibadan, Oyo State, Nigeria. Founded in 2011, it is a young institution that pairs Nigerian academic tradition with a deliberately modern, digital-native operating culture. Its self-image: intellectually confident, humane, structured, ambitious.

- **Motto:** *Knowledge. Character. Impact.*
- **Character:** Teaching-first university with growing research ambitions; strong Computing and Management faculties; close student–faculty ratios presented honestly as a feature of a young institution.
- **Tone of voice:** Plain, warm, precise. No hype, no fake rankings, no invented statistics. Sample content illustrates the product; it never claims real-world facts.

## 2. Users and their primary jobs

| User | Top jobs to be done |
| --- | --- |
| Visitor / prospect | Understand the university; explore programmes; check requirements and fees; find the apply button |
| Applicant | Create account; complete and submit application; upload documents; track status; respond to requests |
| Admitted student | Accept offer; move through onboarding to an activated student account |
| Student | Know today's priorities; register courses; learn (materials, assignments, quizzes); see grades and official results; receive targeted notices |
| Lecturer | Run assigned offerings; publish materials; collect and grade work; communicate with a class |
| Registrar / academic admin | Own academic structure, registration windows, results approval and publication |
| Admissions officer | Review applications, verify documents, issue decisions |
| Super administrator | Manage users, roles, announcements, resources, settings, audit |

## 3. Role model

Fixed institutional roles (PHP enum on `users.role`), enforced server-side by policies and gates. Roles: `super_admin`, `registrar`, `admissions_officer`, `faculty_admin`, `lecturer`, `student`, `applicant`, `finance_officer`, `support_staff`. An applicant becomes a student when an offer is accepted (role transition, never a frontend assumption).

## 4. Product areas

| Area | Scope |
| --- | --- |
| A. Public experience | Marketing/informational site: home, about, academics, admissions info, campus life, research, news & events, resources, contact |
| B. Admissions | Applicant accounts, multi-step application, documents, states, officer review, decisions, offer acceptance |
| C. Digital campus (portal) | Authenticated shells per role; dashboards oriented to "what matters today" |
| D. Learning (LMS) | Offerings → modules → contents; announcements; assignments; quizzes; grades/feedback |
| E. Academic operations | Catalogue, offerings, sessions/semesters, registration with rules, timetable, results lifecycle, transcript |
| F. Resources & services | Resource hub (categories, visibility control), library directory (curated links/files, not circulation), support/help |
| G. Administration | Users & roles, academic structure CRUD, admissions queue, results approval, announcements targeting, reports, audit, settings |

## 5. Information architecture (authenticated)

### Student
Dashboard · My Academics · Courses (LMS) · Registration · Timetable · Assessments · Results · Resources · Payments · Notifications · Support · Settings

### Lecturer
Dashboard · My Courses (manage) · Teaching Schedule · Grading queues · Announcements · Students · Settings

### Administrator (scoped by role)
Dashboard · Users · Academics (structure) · Admissions · Results · Announcements · News & Events · Resources · Reports · Audit · Settings

## 6. Key flows

1. **Visitor → Applicant:** Home → Programme detail → Requirements → Create applicant account → Begin application
2. **Applicant submission:** Wizard steps (personal → education → choices → documents → review) → Submit → Track
3. **Admitted → Student:** Decision shown → Accept offer → Onboarding checklist → Student account activated
4. **Registration:** Basket of eligible offerings → live credit total → server validation (prerequisites, load cap, conflicts, window) → Submit → Confirmation
5. **Learning:** Dashboard → Offering home → Module/Lesson → Assignment → Submit → Feedback/Grade
6. **Assessment:** Lecturer creates assignment/quiz → students submit/attempt → grading queue → release
7. **Results lifecycle:** Component scores → provisional course result → lecturer submits → registrar approves → published officially (students see provisional vs final distinctly)
8. **Resource discovery:** Hub → search/filter → detail → download/access (visibility enforced)
9. **Administration:** Queue → list/filter → detail/action → audit trail entry

## 7. Application state machine

```
draft → submitted → under_review → ┬→ more_info_required → submitted (re-enter)
                                   ├→ accepted            → offer accepted/declined → (onboarding)
                                   ├→ conditionally_accepted
                                   ├→ rejected
                                   └→ waitlisted
any pre-decision state → withdrawn (by applicant)
```

Transitions validated server-side; officers cannot skip review; applicants cannot reopen decided applications.

## 8. Content standards

- All seeded/demo content is plausible and internally consistent (named people, coherent courses, realistic dates relative to the current term).
- No lorem ipsum, no "Test Course", no John Doe.
- Distinguish instantly-downloadable documents from those requiring administrative processing.

## 9. Non-goals (v1)

- Full library circulation/cataloguing (we ship a curated resource directory with an integration-friendly shape).
- Proctored online examinations (quizzes are timed and server-enforced, but we make no proctoring claims).
- Live payment gateway credentials (provider abstraction + manual/dev verification mode).
- Mobile native apps.
