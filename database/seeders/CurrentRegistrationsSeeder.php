<?php

namespace Database\Seeders;

use App\Models\CourseOffering;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Approved current-semester registrations. Named personas get their exact
 * baskets (docs/SEED.md); filler students are matched to level-appropriate
 * offerings so classlists and gradebooks have population.
 */
class CurrentRegistrationsSeeder extends Seeder
{
    /** student email => course codes */
    private const BASKETS = [
        'z.adeyemi@student.olodo.edu.ng' => ['CSC 301', 'CSC 303', 'CSC 304', 'CSC 305', 'CSC 308', 'GST 301'], // 17 credits
        'd.okon@student.olodo.edu.ng' => ['BUS 201', 'ACC 201', 'GST 201'],
        'f.alade@student.olodo.edu.ng' => ['CSC 401', 'CSC 402', 'CSC 305'],
    ];

    /** level => course codes offered this semester */
    private const LEVEL_MENU = [
        100 => ['STA 105', 'BUS 101', 'ACC 101', 'EEE 201'],
        200 => ['CSC 201', 'CSC 202', 'BUS 201', 'ACC 201', 'GST 201', 'EEE 201'],
        300 => ['CSC 301', 'CSC 303', 'CSC 304', 'CSC 305', 'CSC 308', 'GST 301', 'MTH 205'],
        400 => ['CSC 401', 'CSC 402', 'CSC 305'],
    ];

    public function run(): void
    {
        $semester = Semester::where('is_current', true)->firstOrFail();
        $regOpenedAt = $semester->registration_opens_at ?? now()->subDays(10);

        $profiles = StudentProfile::with('user')->get();

        foreach ($profiles as $profile) {
            $email = $profile->user->email;
            $codes = self::BASKETS[$email]
                ?? array_slice(self::LEVEL_MENU[$profile->level] ?? [], 0, random_int(3, 4));

            if ($codes === []) {
                continue;
            }

            $registration = Registration::create([
                'student_id' => $profile->user_id,
                'semester_id' => $semester->id,
                'status' => 'approved',
                'submitted_at' => $regOpenedAt->copy()->addDays(random_int(1, 6)),
                'approved_at' => $regOpenedAt->copy()->addDays(8),
            ]);

            foreach ($codes as $code) {
                $offeringId = CourseOffering::query()
                    ->where('semester_id', $semester->id)
                    ->whereHas('course', fn ($q) => $q->where('code', $code))
                    ->value('id');

                if ($offeringId === null) {
                    continue;
                }

                RegistrationItem::firstOrCreate([
                    'registration_id' => $registration->id,
                    'course_offering_id' => $offeringId,
                ], ['status' => 'registered']);
            }
        }

        // One deliberate demo case: a submitted-but-unapproved registration for the
        // registrar's queue (a late registrant).
        $lateStudent = User::where('role', 'student')
            ->whereDoesntHave('registrations', fn ($q) => $q->where('semester_id', $semester->id))
            ->first();

        if ($lateStudent !== null) {
            Registration::create([
                'student_id' => $lateStudent->id,
                'semester_id' => $semester->id,
                'status' => 'submitted',
                'submitted_at' => now()->subDays(2),
            ]);
        }
    }
}
