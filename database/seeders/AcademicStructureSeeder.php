<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use Illuminate\Database\Seeder;

/**
 * Faculties → departments → programmes → course catalogue (+ prerequisite chains),
 * exactly as specified in docs/SEED.md §Institutional structure.
 */
class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $fcs = Faculty::create([
            'name' => 'Faculty of Computing & Information Sciences',
            'code' => 'FCS',
            'slug' => 'computing-information-sciences',
            'summary' => 'Computing education built on strong fundamentals: programming, systems, data, and the discipline to apply them well.',
        ]);
        $fms = Faculty::create([
            'name' => 'Faculty of Management Sciences',
            'code' => 'FMS',
            'slug' => 'management-sciences',
            'summary' => 'Management education for organisations that value analysis, ethics, and execution.',
        ]);
        $fns = Faculty::create([
            'name' => 'Faculty of Natural & Applied Sciences',
            'code' => 'FNS',
            'slug' => 'natural-applied-sciences',
            'summary' => 'Mathematics and statistics — the quantitative backbone of modern science and industry.',
        ]);
        $feg = Faculty::create([
            'name' => 'Faculty of Engineering',
            'code' => 'FEG',
            'slug' => 'engineering',
            'summary' => 'Engineering training grounded in theory, workshop practice, and professional responsibility.',
        ]);

        $csc = Department::create(['faculty_id' => $fcs->id, 'name' => 'Department of Computer Science', 'code' => 'CSC', 'slug' => 'computer-science', 'summary' => 'Core computing: programming, systems, algorithms, and intelligent applications.']);
        $bus = Department::create(['faculty_id' => $fms->id, 'name' => 'Department of Business Administration', 'code' => 'BUS', 'slug' => 'business-administration', 'summary' => 'Managing people, operations, and strategy in resource-conscious organisations.']);
        $acc = Department::create(['faculty_id' => $fms->id, 'name' => 'Department of Accounting', 'code' => 'ACC', 'slug' => 'accounting', 'summary' => 'Financial reporting, assurance, and accountability.']);
        $mth = Department::create(['faculty_id' => $fns->id, 'name' => 'Department of Mathematical Sciences', 'code' => 'MTH', 'slug' => 'mathematical-sciences', 'summary' => 'Pure, applied, and statistical mathematics with real-world applications.']);
        $eee = Department::create(['faculty_id' => $feg->id, 'name' => 'Department of Electrical & Electronic Engineering', 'code' => 'EEE', 'slug' => 'electrical-electronic-engineering', 'summary' => 'Electrical systems, electronics, and power — from circuits to grids.']);

        $programmes = [
            [$csc, 'B.Sc. Computer Science', 'CSC-BS', 'bsc-computer-science'],
            [$csc, 'B.Sc. Software Engineering', 'SWE-BS', 'bsc-software-engineering'],
            [$csc, 'B.Sc. Data Science', 'DSC-BS', 'bsc-data-science'],
            [$bus, 'B.Sc. Business Administration', 'BUS-BS', 'bsc-business-administration'],
            [$acc, 'B.Sc. Accounting', 'ACC-BS', 'bsc-accounting'],
            [$mth, 'B.Sc. Mathematics', 'MTH-BS', 'bsc-mathematics'],
            [$mth, 'B.Sc. Statistics', 'STA-BS', 'bsc-statistics'],
            [$eee, 'B.Eng. Electrical & Electronic Engineering', 'EEE-BE', 'beng-electrical-electronic-engineering'],
        ];

        foreach ($programmes as [$dept, $name, $code, $slug]) {
            Programme::create([
                'department_id' => $dept->id,
                'name' => $name,
                'code' => $code,
                'slug' => $slug,
                'award' => str_starts_with($code, 'EEE') ? 'beng' : 'bsc',
                'duration_semesters' => 8,
                'description' => $this->programmeDescription($name),
                'entry_requirements' => 'Five O-level credits (WAEC/NECO/NABTEB) including English Language and Mathematics, obtained in not more than two sittings, plus a pass in the University of Olodo entrance examination.',
                'tuition_per_session' => $dept->is($fcs) ? 42_000_000 : ($dept->is($feg) ? 45_000_000 : 35_000_000), // kobo
            ]);
        }

        // --- Course catalogue ---------------------------------------------------
        $courses = [
            // [department, code, title, units, level]
            [$csc, 'CSC 101', 'Introduction to Computing', 3, 100],
            [$csc, 'CSC 102', 'Computer Programming I', 3, 100],
            [$csc, 'CSC 201', 'Computer Programming II', 3, 200],
            [$csc, 'CSC 202', 'Discrete Structures', 3, 200],
            [$csc, 'CSC 301', 'Data Structures & Algorithms', 3, 300],
            [$csc, 'CSC 303', 'Computer Architecture', 3, 300],
            [$csc, 'CSC 304', 'Operating Systems I', 4, 300],
            [$csc, 'CSC 305', 'Database Systems I', 3, 300],
            [$csc, 'CSC 308', 'Software Engineering Principles', 3, 300],
            [$csc, 'CSC 401', 'Machine Learning Fundamentals', 3, 400],
            [$csc, 'CSC 402', 'Distributed Systems', 3, 400],
            [$csc, 'SWE 201', 'Software Engineering Fundamentals', 3, 200],
            [$csc, 'SWE 301', 'Requirements Engineering', 3, 300],
            [$csc, 'DSC 201', 'Introduction to Data Science', 3, 200],
            [$csc, 'DSC 301', 'Statistical Learning', 3, 300],
            [$mth, 'MTH 101', 'Elementary Mathematics I', 3, 100],
            [$mth, 'MTH 102', 'Elementary Mathematics II', 3, 100],
            [$mth, 'MTH 205', 'Linear Algebra', 3, 200],
            [$mth, 'MTH 201', 'Mathematical Methods I', 4, 200],
            [$mth, 'STA 105', 'Basic Statistics', 3, 100],
            [$mth, 'STA 205', 'Probability Theory I', 3, 200],
            [$bus, 'BUS 101', 'Principles of Management', 3, 100],
            [$bus, 'BUS 201', 'Organisational Behaviour', 3, 200],
            [$bus, 'BUS 301', 'Entrepreneurship & Innovation', 3, 300],
            [$acc, 'ACC 101', 'Principles of Accounting I', 3, 100],
            [$acc, 'ACC 201', 'Financial Accounting II', 3, 200],
            [$eee, 'EEE 101', 'Circuit Theory I', 3, 100],
            [$eee, 'EEE 201', 'Circuit Theory II', 3, 200],
            [$eee, 'EEE 301', 'Electromagnetic Fields', 3, 300],
            [$csc, 'GST 101', 'Use of English I', 2, 100],
            [$csc, 'GST 102', 'Use of English II', 2, 100],
            [$csc, 'GST 201', 'Peace & Conflict Resolution', 2, 200],
            [$csc, 'GST 301', 'Entrepreneurship Practice', 1, 300],
        ];

        $courseIds = [];
        foreach ($courses as [$dept, $code, $title, $units, $level]) {
            $course = Course::create([
                'department_id' => $dept->id,
                'code' => $code,
                'title' => $title,
                'credit_units' => $units,
                'level' => $level,
                'description' => $this->courseDescription($code, $title),
            ]);
            $courseIds[$code] = $course->id;
        }

        $prerequisites = [
            'CSC 102' => ['CSC 101'],
            'CSC 201' => ['CSC 102'],
            'CSC 202' => ['CSC 102'],
            'CSC 301' => ['CSC 201'],
            'CSC 303' => ['CSC 202'],
            'CSC 304' => ['CSC 201'],
            'CSC 305' => ['CSC 201'],
            'CSC 308' => ['CSC 201'],
            'CSC 401' => ['CSC 301', 'STA 105'],
            'CSC 402' => ['CSC 301'],
            'SWE 301' => ['SWE 201'],
            'DSC 301' => ['DSC 201', 'MTH 205'],
            'MTH 102' => ['MTH 101'],
            'MTH 201' => ['MTH 102'],
            'MTH 205' => ['MTH 101'],
            'STA 205' => ['STA 105'],
            'BUS 201' => ['BUS 101'],
            'BUS 301' => ['BUS 201'],
            'ACC 201' => ['ACC 101'],
            'EEE 201' => ['EEE 101'],
            'EEE 301' => ['EEE 201'],
            'GST 102' => ['GST 101'],
        ];

        foreach ($prerequisites as $courseCode => $required) {
            foreach ($required as $preCode) {
                CoursePrerequisite::create([
                    'course_id' => $courseIds[$courseCode],
                    'prerequisite_course_id' => $courseIds[$preCode],
                ]);
            }
        }
    }

    private function programmeDescription(string $name): string
    {
        return match ($name) {
            'B.Sc. Computer Science' => 'A rigorous grounding in computing fundamentals — programming, algorithms, systems, and databases — with room to specialise in advanced topics such as machine learning and distributed systems.',
            'B.Sc. Software Engineering' => 'Beyond writing code: requirements, design, testing, and teamwork on real software projects, preparing graduates for disciplined engineering practice.',
            'B.Sc. Data Science' => 'Statistics, computation, and domain thinking for turning data into decisions — from exploratory analysis to statistical learning.',
            'B.Sc. Business Administration' => 'A broad management education covering organisational behaviour, operations, marketing, and entrepreneurship for Nigeria\'s dynamic business environment.',
            'B.Sc. Accounting' => 'Financial accounting, management accounting, audit, and taxation, with the professional ethics the profession demands.',
            'B.Sc. Mathematics' => 'Analysis, algebra, and applied mathematics taught for depth of understanding and breadth of application.',
            'B.Sc. Statistics' => 'Probability, inference, and data analysis for research, industry, and public decision-making.',
            default => 'Accredited engineering education combining circuit theory, electronics, machines, and power systems with laboratory and workshop practice.',
        };
    }

    private function courseDescription(string $code, string $title): string
    {
        return "{$code} — {$title}. Weekly lectures with continuous assessment (40%) and a written examination (60%). See the offering page for the current syllabus and materials.";
    }
}
