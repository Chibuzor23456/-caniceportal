<?php

namespace Tests\Feature;

use App\Jobs\DeployApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeployWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function signedHeaders(array $payload, ?string $event = 'push'): array
    {
        $secret = config('services.deploy_webhook.secret');
        $json = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $json, $secret);

        return array_filter([
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => $event,
        ]);
    }

    public function test_a_valid_push_to_main_dispatches_the_deploy_job(): void
    {
        Bus::fake();
        config(['services.deploy_webhook.secret' => 'test-secret']);

        $payload = ['ref' => 'refs/heads/main'];

        $response = $this->postJson(route('deploy.webhook'), $payload, $this->signedHeaders($payload));

        $response->assertStatus(202);
        Bus::assertDispatched(DeployApplication::class);
    }

    public function test_an_invalid_signature_is_rejected_and_nothing_is_dispatched(): void
    {
        Bus::fake();
        config(['services.deploy_webhook.secret' => 'test-secret']);

        $response = $this->postJson(route('deploy.webhook'), ['ref' => 'refs/heads/main'], [
            'X-Hub-Signature-256' => 'sha256=not-the-right-signature',
            'X-GitHub-Event' => 'push',
        ]);

        $response->assertStatus(403);
        Bus::assertNotDispatched(DeployApplication::class);
    }

    public function test_a_missing_signature_header_is_rejected(): void
    {
        Bus::fake();
        config(['services.deploy_webhook.secret' => 'test-secret']);

        $response = $this->postJson(route('deploy.webhook'), ['ref' => 'refs/heads/main'], [
            'X-GitHub-Event' => 'push',
        ]);

        $response->assertStatus(403);
        Bus::assertNotDispatched(DeployApplication::class);
    }

    public function test_an_unconfigured_secret_rejects_every_request(): void
    {
        Bus::fake();
        config(['services.deploy_webhook.secret' => null]);

        $payload = ['ref' => 'refs/heads/main'];
        $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), 'anything');

        $response = $this->postJson(route('deploy.webhook'), $payload, [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'push',
        ]);

        $response->assertStatus(403);
        Bus::assertNotDispatched(DeployApplication::class);
    }

    public function test_non_push_events_are_acknowledged_but_ignored(): void
    {
        Bus::fake();
        config(['services.deploy_webhook.secret' => 'test-secret']);

        $payload = ['zen' => 'Responsive is better than fast.'];

        $response = $this->postJson(route('deploy.webhook'), $payload, $this->signedHeaders($payload, 'ping'));

        $response->assertStatus(200);
        Bus::assertNotDispatched(DeployApplication::class);
    }

    public function test_pushes_to_branches_other_than_main_are_ignored(): void
    {
        Bus::fake();
        config(['services.deploy_webhook.secret' => 'test-secret']);

        $payload = ['ref' => 'refs/heads/feature/something'];

        $response = $this->postJson(route('deploy.webhook'), $payload, $this->signedHeaders($payload));

        $response->assertStatus(200);
        Bus::assertNotDispatched(DeployApplication::class);
    }
}
