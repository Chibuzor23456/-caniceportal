<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        return view('admin.projects.index');
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load('client', 'quotation', 'phases', 'invoices');

        return view('admin.projects.show', ['project' => $project]);
    }
}
