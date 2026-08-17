<?php

namespace App\Http\Controllers;

use App\Jobs\DeployApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GitHub push webhook. Public by necessity (GitHub has to reach it), so the
 * signature check is the only thing standing between this and anyone on the
 * internet triggering a deploy - see README "Auto-Deploy" before touching
 * this file.
 */
class DeployWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            return response('Invalid signature.', 403);
        }

        if ($request->header('X-GitHub-Event') !== 'push') {
            return response('Ignored: not a push event.', 200);
        }

        if ($request->input('ref') !== 'refs/heads/main') {
            return response('Ignored: not a push to main.', 200);
        }

        DeployApplication::dispatch();

        return response('Deploy queued.', 202);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = config('services.deploy_webhook.secret');

        if (empty($secret)) {
            return false;
        }

        $signature = (string) $request->header('X-Hub-Signature-256');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
