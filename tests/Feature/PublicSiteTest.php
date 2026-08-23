<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\Faculty;
use App\Models\NewsArticle;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CampusLifeSeeder;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\SupportStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_academics_index_lists_all_faculties_and_programmes(): void
    {
        $this->get('/academics')
            ->assertOk()
            ->assertSee('Faculty of Computing & Information Sciences')
            ->assertSee('B.Sc. Computer Science')
            ->assertSee('B.Eng. Electrical & Electronic Engineering');
    }

    public function test_programme_detail_shows_requirements_fees_and_structure(): void
    {
        $programme = Faculty::query()
            ->where('code', 'FCS')
            ->first()
            ->departments()->where('code', 'CSC')->first()
            ->programmes()->where('code', 'CSC-BS')->first();

        $this->get("/academics/{$programme->slug}")
            ->assertOk()
            ->assertSee('Entry requirements')
            ->assertSee('Five O-level credits')
            ->assertSee('₦'.number_format($programme->tuition_per_session / 100))
            ->assertSee('Apply for this programme')
            ->assertSee('CSC 201'); // among the sampled early courses
    }

    public function test_unknown_programme_slugs_404(): void
    {
        $this->get('/academics/no-such-programme')->assertNotFound();
    }

    public function test_admissions_page_describes_process_and_fees(): void
    {
        $this->seed(CalendarSeeder::class);

        $this->get('/admissions')
            ->assertOk()
            ->assertSee('The application process')
            ->assertSee('₦10,000')
            ->assertSee('Now admitting for');
    }

    public function test_news_listing_and_article_pages_work(): void
    {
        // News/events reference staff authors and support assignees.
        $this->seed(CalendarSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(SupportStaffSeeder::class);
        $this->seed(CampusLifeSeeder::class);

        $article = NewsArticle::published()->firstOrFail();

        $this->get('/news')
            ->assertOk()
            ->assertSee($article->title)
            ->assertSee('Upcoming events');

        $this->get("/news/{$article->slug}")
            ->assertOk()
            ->assertSee($article->title);
    }

    public function test_contact_form_persists_an_enquiry(): void
    {
        $this->post('/contact', [
            'name' => 'Adebayo Ogundimu',
            'email' => 'adebayo@example.com',
            'phone' => null,
            'subject' => 'Admissions enquiry',
            'message' => 'When does the next entrance examination hold?',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('contact_enquiries', [
            'email' => 'adebayo@example.com',
            'status' => 'new',
        ]);
    }

    public function test_contact_form_rejects_invalid_input(): void
    {
        $this->from('/contact')->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => 'x',
            'message' => '',
        ])->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_about_page_states_the_institution_honestly(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('founded in 2011')
            ->assertSee('Knowledge. Character. Impact.');
    }
}
