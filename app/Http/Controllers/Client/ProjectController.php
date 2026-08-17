<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->where('client_id', $request->user()->client?->id)
            ->with('phases')
            ->latest('created_at')
            ->get();

        return view('client.projects.index', ['projects' => $projects]);
    }

    public function show(Project $project, Request $request): View
    {
        abort_unless($project->client_id === $request->user()->client?->id, 404);

        $project->load('client', 'quotation', 'phases');

        return view('client.projects.show', ['project' => $project]);
    }
}
