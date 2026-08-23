<?php

namespace Tests\Feature;

use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_presents_the_institution(): void
    {
        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('University of Olodo')
            ->assertSee('Knowledge. Character. Impact.')
            ->assertSee('B.Sc. Computer Science');
    }

    public function test_homepage_shows_live_registration_notice(): void
    {
        $this->seed(CalendarSeeder::class);

        $this->get('/')->assertSee('Registration open');
    }
}
