<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Projects\ApprovePhaseAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'two_factor_confirmed_at' => now()]);
    }

    private function client(string $email = 'jamie@acme.test'): Client
    {
        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => $email,
        ]);

        $client->user->forceFill(['must_change_password' => false])->save();

        return $client;
    }

    private function completedProject(Client $client, User $admin): Project
    {
        $this->actingAs($admin);

        $quotation = (new CreateQuotationAction)->handle($client);

        $project = Project::create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'title' => 'Acme Website',
            'status' => ProjectStatus::Active,
        ]);

        $phase = $project->phases()->create(['name' => 'Design', 'order' => 0, 'status' => PhaseStatus::PendingReview]);

        app(ApprovePhaseAction::class)->handle($phase);

        return $project->fresh();
    }

    public function test_completing_a_project_creates_a_pending_testimonial_prompt(): void
    {
        Mail::fake();

        $client = $this->client();
        $project = $this->completedProject($client, $this->admin());

        $testimonial = Testimonial::where('project_id', $project->id)->first();

        $this->assertNotNull($testimonial);
        $this->assertNull($testimonial->submitted_at);
        $this->assertNull($testimonial->rating);

        $this->actingAs($client->user);
        $this->get(route('client.dashboard'))->assertOk()->assertSee('How did we do');
    }

    public function test_client_can_submit_a_testimonial_and_it_notifies_admins(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->completedProject($client, $admin);
        $testimonial = Testimonial::where('project_id', $project->id)->first();

        $this->actingAs($client->user);

        $response = $this->post(route('client.testimonials.store', $testimonial), [
            'rating' => 5,
            'comment' => 'Fantastic work!',
        ]);

        $response->assertRedirect();
        $testimonial->refresh();
        $this->assertSame(5, $testimonial->rating);
        $this->assertSame('Fantastic work!', $testimonial->comment);
        $this->assertNotNull($testimonial->submitted_at);

        $this->assertTrue($admin->notifications->contains(fn ($n) => $n->data['title'] === 'New testimonial'));
    }

    public function test_a_client_cannot_submit_a_testimonial_for_another_clients_project(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');
        $project = $this->completedProject($clientA, $admin);
        $testimonial = Testimonial::where('project_id', $project->id)->first();

        $this->actingAs($clientB->user);

        $response = $this->post(route('client.testimonials.store', $testimonial), ['rating' => 3]);

        $response->assertNotFound();
        $this->assertNull($testimonial->fresh()->submitted_at);
    }

    public function test_a_client_cannot_resubmit_an_already_submitted_testimonial(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->completedProject($client, $admin);
        $testimonial = Testimonial::where('project_id', $project->id)->first();
        $testimonial->forceFill(['rating' => 4, 'submitted_at' => now()])->save();

        $this->actingAs($client->user);

        $response = $this->post(route('client.testimonials.store', $testimonial), ['rating' => 1]);

        $response->assertNotFound();
        $this->assertSame(4, $testimonial->fresh()->rating);
    }

    public function test_admin_testimonials_index_lists_only_submitted_testimonials(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->completedProject($client, $admin);
        $testimonial = Testimonial::where('project_id', $project->id)->first();
        $testimonial->forceFill(['rating' => 5, 'comment' => 'Great!', 'submitted_at' => now()])->save();

        $this->actingAs($admin);
        $response = $this->get(route('admin.testimonials.index'));

        $response->assertOk();
        $response->assertSee('Great!');
    }
}
