<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Mail\DeliverableUploadedMail;
use App\Mail\PhaseApprovedMail;
use App\Mail\PhaseCommentMail;
use App\Mail\ProjectCompletedMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectReviewFlowTest extends TestCase
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

        // Skip the forced first-login password change so tests can hit the
        // client app routes directly, matching the other client-route tests.
        $client->user->forceFill(['must_change_password' => false])->save();

        return $client;
    }

    /**
     * CreateQuotationAction reads auth()->id() for created_by, so this must
     * run while an admin is the acting user; callers switch actingAs() to
     * whichever party they need immediately after.
     */
    private function projectWithTwoPhases(Client $client, User $admin): Project
    {
        $this->actingAs($admin);

        $quotation = (new CreateQuotationAction)->handle($client);

        $project = Project::create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'title' => 'Acme Website',
            'status' => ProjectStatus::Active,
        ]);

        $project->phases()->create(['name' => 'Design', 'order' => 0, 'status' => PhaseStatus::NotStarted]);
        $project->phases()->create(['name' => 'Development', 'order' => 1, 'status' => PhaseStatus::NotStarted]);

        return $project->fresh('phases');
    }

    public function test_admin_uploading_a_deliverable_flips_the_phase_to_pending_review_and_notifies_the_client(): void
    {
        Mail::fake();
        Storage::fake('local');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $admin);
        $phase = $project->phases->first();

        $this->actingAs($admin);

        Livewire::test('projects.phase-thread', ['phase' => $phase, 'role' => 'admin'])
            ->set('files', [UploadedFile::fake()->create('mockup.pdf', 200)])
            ->set('link', 'https://staging.example.test')
            ->set('deliverableNotes', 'First pass at the homepage.')
            ->call('uploadDeliverable');

        $phase->refresh();
        $this->assertSame(PhaseStatus::PendingReview, $phase->status);
        $this->assertSame(1, $phase->deliverables()->count());
        $this->assertSame(1, $phase->deliverables()->first()->files()->count());

        Mail::assertQueued(DeliverableUploadedMail::class);
    }

    public function test_comments_flip_the_phase_to_in_discussion_and_notify_whichever_party_didnt_just_comment(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $admin);
        $phase = $project->phases->first();
        $phase->forceFill(['status' => PhaseStatus::PendingReview])->save();

        $this->actingAs($client->user);

        Livewire::test('projects.phase-thread', ['phase' => $phase, 'role' => 'client'])
            ->set('commentBody', 'Can we change the header color?')
            ->call('addComment');

        $phase->refresh();
        $this->assertSame(PhaseStatus::InDiscussion, $phase->status);
        $this->assertSame(1, $phase->comments()->count());
        Mail::assertQueued(PhaseCommentMail::class);

        $this->actingAs($admin);

        Livewire::test('projects.phase-thread', ['phase' => $phase, 'role' => 'admin'])
            ->set('commentBody', 'Sure, updated it to navy.')
            ->call('addComment');

        $phase->refresh();
        $this->assertSame(PhaseStatus::InDiscussion, $phase->status);
        $this->assertSame(2, $phase->comments()->count());
        Mail::assertQueued(PhaseCommentMail::class, fn ($m) => $m->comment->body === 'Sure, updated it to navy.');
    }

    public function test_approving_a_phase_locks_the_thread_and_unlocks_the_next_phase(): void
    {
        Mail::fake();

        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $this->admin());
        $firstPhase = $project->phases->first();
        $secondPhase = $project->phases->last();
        $firstPhase->forceFill(['status' => PhaseStatus::PendingReview])->save();

        $this->assertFalse($secondPhase->isUnlocked());

        $this->actingAs($client->user);

        Livewire::test('projects.phase-thread', ['phase' => $firstPhase, 'role' => 'client'])
            ->call('approve')
            ->assertSet('canApprove', false);

        $firstPhase->refresh();
        $this->assertSame(PhaseStatus::Approved, $firstPhase->status);
        $this->assertNotNull($firstPhase->approved_at);
        $this->assertTrue($secondPhase->fresh()->isUnlocked());

        Mail::assertQueued(PhaseApprovedMail::class);

        $project->refresh();
        $this->assertSame(50, $project->progressPercentage());
        $this->assertSame(ProjectStatus::Active, $project->status);
    }

    public function test_approving_the_last_phase_completes_the_project_and_notifies_both_parties(): void
    {
        Mail::fake();

        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $this->admin());
        $firstPhase = $project->phases->first();
        $secondPhase = $project->phases->last();

        $firstPhase->forceFill(['status' => PhaseStatus::Approved, 'approved_at' => now()])->save();
        $secondPhase->forceFill(['status' => PhaseStatus::PendingReview])->save();

        $this->actingAs($client->user);

        Livewire::test('projects.phase-thread', ['phase' => $secondPhase, 'role' => 'client'])
            ->call('approve');

        $project->refresh();
        $this->assertSame(ProjectStatus::Completed, $project->status);
        $this->assertNotNull($project->completion_date);
        $this->assertSame(100, $project->progressPercentage());

        Mail::assertQueued(ProjectCompletedMail::class, fn ($m) => ! $m->forAdmin);
        Mail::assertQueued(ProjectCompletedMail::class, fn ($m) => $m->forAdmin);
    }

    public function test_a_client_cannot_approve_a_phase_that_isnt_pending_review_or_in_discussion(): void
    {
        Mail::fake();

        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $this->admin());
        $notStartedPhase = $project->phases->first();

        $this->actingAs($client->user);

        Livewire::test('projects.phase-thread', ['phase' => $notStartedPhase, 'role' => 'client'])
            ->assertSet('canApprove', false);

        $notStartedPhase->refresh();
        $this->assertSame(PhaseStatus::NotStarted, $notStartedPhase->status);
    }

    public function test_admin_can_view_the_projects_index_and_show_pages(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $admin);

        $this->actingAs($admin);

        $this->get(route('admin.projects.index'))->assertOk();
        $this->get(route('admin.projects.show', $project))->assertOk()->assertSee('Design');
    }

    public function test_client_can_view_their_own_projects_index_and_show_pages(): void
    {
        Mail::fake();

        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $this->admin());

        $this->actingAs($client->user);

        $this->get(route('client.projects.index'))->assertOk()->assertSee('Acme Website');
        $this->get(route('client.projects.show', $project))->assertOk()->assertSee('Design');
    }

    public function test_admin_and_client_dashboards_render_with_real_project_progress_data(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithTwoPhases($client, $admin);
        $firstPhase = $project->phases->first();
        $firstPhase->forceFill(['status' => PhaseStatus::Approved, 'approved_at' => now()])->save();
        $project->phases->last()->forceFill(['status' => PhaseStatus::PendingReview])->save();

        $this->actingAs($admin);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Phases Awaiting Review');

        $this->actingAs($client->user);
        $this->get(route('client.dashboard'))->assertOk()->assertSee('50%');
    }

    public function test_a_client_cannot_view_another_clients_project(): void
    {
        Mail::fake();

        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');
        $project = $this->projectWithTwoPhases($clientA, $this->admin());

        $this->actingAs($clientB->user);

        $response = $this->get(route('client.projects.show', $project));

        $response->assertNotFound();
    }
}
