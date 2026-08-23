<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts (docs/SEED.md): staff, named students, applicants, plus a
 * factory-generated student population so tables and queues have people in them.
 * Every account uses password `password`.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // --- Staff ---------------------------------------------------------------
        $staff = [
            ['Amara Okafor', 'admin@olodo.edu.ng', UserRole::SuperAdmin],
            ['Tunde Bakare', 'registrar@olodo.edu.ng', UserRole::Registrar],
            ['Ngozi Eze', 'admissions@olodo.edu.ng', UserRole::AdmissionsOfficer],
            ['Sani Garba', 'finance@olodo.edu.ng', UserRole::FinanceOfficer],
            ['Dr. Chiamaka Obi', 'c.obi@olodo.edu.ng', UserRole::Lecturer],
            ['Mr. Yusuf Ibrahim', 'y.ibrahim@olodo.edu.ng', UserRole::Lecturer],
            ['Dr. Ada Umeh', 'a.umeh@olodo.edu.ng', UserRole::Lecturer],
            ['Dr. Emeka Uche', 'e.uche@olodo.edu.ng', UserRole::Lecturer],
            ['Mrs. Blessing Adeoye', 'b.adeoye@olodo.edu.ng', UserRole::Lecturer],
            ['Engr. Kabir Lawal', 'k.lawal@olodo.edu.ng', UserRole::Lecturer],
        ];

        $users = [];
        foreach ($staff as [$name, $email, $role]) {
            $users[$email] = User::create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $password,
                'role' => $role->value,
            ]);
        }

        $csc = Department::where('code', 'CSC')->first();
        $bus = Department::where('code', 'BUS')->first();
        $mth = Department::where('code', 'MTH')->first();
        $acc = Department::where('code', 'ACC')->first();
        $eee = Department::where('code', 'EEE')->first();

        $csc->update(['head_id' => $users['c.obi@olodo.edu.ng']->id]);
        $bus->update(['head_id' => $users['y.ibrahim@olodo.edu.ng']->id]);
        $acc->update(['head_id' => $users['b.adeoye@olodo.edu.ng']->id]);
        $mth->update(['head_id' => $users['e.uche@olodo.edu.ng']->id]);
        $eee->update(['head_id' => $users['k.lawal@olodo.edu.ng']->id]);
        $csc->faculty->update(['dean_id' => $users['a.umeh@olodo.edu.ng']->id]);
        $bus->faculty->update(['dean_id' => $users['y.ibrahim@olodo.edu.ng']->id]);

        // --- Named students (docs/SEED.md personas) ------------------------------
        $programmes = Programme::all()->keyBy('code');
        $currentSession = AcademicSession::current();

        $students = [
            // name, email, programme code, level, matric, adviser email
            ['Zainab Adeyemi', 'z.adeyemi@student.olodo.edu.ng', 'CSC-BS', 300, 'UO/CSC/23/0187', 'c.obi@olodo.edu.ng'],
            ['David Okon', 'd.okon@student.olodo.edu.ng', 'BUS-BS', 200, 'UO/BUS/24/0103', 'y.ibrahim@olodo.edu.ng'],
            ['Funmi Alade', 'f.alade@student.olodo.edu.ng', 'CSC-BS', 400, 'UO/CSC/22/0091', 'a.umeh@olodo.edu.ng'],
        ];

        foreach ($students as [$name, $email, $progCode, $level, $matric, $adviser]) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $password,
                'role' => UserRole::Student->value,
            ]);

            StudentProfile::create([
                'user_id' => $user->id,
                'matric_number' => $matric,
                'programme_id' => $programmes[$progCode]->id,
                'level' => $level,
                'adviser_id' => $users[$adviser]->id,
                'status' => 'active',
                'admitted_session_id' => $this->sessionAdmittedIn($level, $currentSession),
            ]);
        }

        // --- Wider student population (fills tables, queues, gradebooks) --------
        $fillerNames = [
            'Chiamaka Nwankwo', 'Ibrahim Suleiman', 'Tolu Ogunleye', 'Nnamdi Kalu',
            'Halima Abubakar', 'Segun Adewale', 'Blessing Etim', 'Kelechi Obiora',
            'Maryam Sani', 'Femi Balogun', 'Rita Ekanem', 'Uche Maduka',
        ];
        $fillerProgrammes = ['CSC-BS', 'CSC-BS', 'SWE-BS', 'DSC-BS', 'BUS-BS', 'ACC-BS', 'STA-BS', 'MTH-BS', 'EEE-BE', 'CSC-BS', 'DSC-BS', 'BUS-BS'];
        $fillerLevels = [300, 200, 200, 300, 200, 400, 300, 200, 100, 400, 200, 300];
        $deptCodes = ['CSC', 'CSC', 'CSC', 'CSC', 'BUS', 'ACC', 'MTH', 'MTH', 'EEE', 'CSC', 'CSC', 'BUS'];

        $seq = 100;
        foreach ($fillerNames as $i => $name) {
            $user = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)).'@student.olodo.edu.ng',
                'email_verified_at' => now(),
                'password' => $password,
                'role' => UserRole::Student->value,
            ]);

            $deptCode = $deptCodes[$i];

            StudentProfile::create([
                'user_id' => $user->id,
                'matric_number' => sprintf('UO/%s/%02d/%04d', $deptCode, $this->admissionYear($fillerLevels[$i], $currentSession) % 100, $seq += 7),
                'programme_id' => $programmes[$fillerProgrammes[$i]]->id,
                'level' => $fillerLevels[$i],
                'adviser_id' => $users['c.obi@olodo.edu.ng']->id,
                'status' => 'active',
                'admitted_session_id' => $this->sessionAdmittedIn($fillerLevels[$i], $currentSession),
            ]);
        }

        // --- Applicants ----------------------------------------------------------
        // Named personas get their full applications in AdmissionsSeeder; here we
        // create their accounts so the seeder order stays simple.
    }

    /** The academic session a student at $level was admitted in (relative to current). */
    private function sessionAdmittedIn(int $level, AcademicSession $current): int
    {
        $yearsAgo = intdiv($level, 100) - 1;
        $startYear = (int) substr($current->name, 0, 4) - $yearsAgo;

        return AcademicSession::firstWhere('name', "{$startYear}/".($startYear + 1))?->id ?? $current->id;
    }

    private function admissionYear(int $level, AcademicSession $current): int
    {
        return (int) substr($current->name, 0, 4) - (intdiv($level, 100) - 1);
    }
}
