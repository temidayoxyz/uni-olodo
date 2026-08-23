<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Orchestrates the seeded University of Olodo world (docs/SEED.md).
 * Order matters: structure → calendar → people → offerings → history →
 * current registrations → learning content → admissions → campus life.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AcademicStructureSeeder::class,
            CalendarSeeder::class,
            DemoUsersSeeder::class,
            SupportStaffSeeder::class,
            OfferingsSeeder::class,
            AcademicHistorySeeder::class,
            CurrentRegistrationsSeeder::class,
            LearningSeeder::class,
            AdmissionsSeeder::class,
            CampusLifeSeeder::class,
        ]);
    }
}
