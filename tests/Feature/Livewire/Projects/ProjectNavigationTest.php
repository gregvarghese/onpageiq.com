<?php

use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'organization_id' => $this->user->organization_id,
    ]);
});

describe('Project Navigation Component', function () {
    it('renders on project dashboard', function () {
        $response = $this->get(route('projects.show', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Architecture');
        $response->assertSee('Dictionary');
    });

    it('renders on project dictionary', function () {
        // Dictionary page may redirect if user doesn't have certain permissions
        $response = $this->get(route('projects.dictionary', $this->project));

        // Follow any redirects
        if ($response->status() === 302) {
            $response = $this->get($response->headers->get('Location'));
        }

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Architecture');
        $response->assertSee('Dictionary');
    });

    it('renders on site architecture page', function () {
        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Architecture');
        $response->assertSee('Dictionary');
    });

    it('shows correct active state on dashboard', function () {
        $response = $this->get(route('projects.show', $this->project));

        $response->assertStatus(200);
        // Dashboard tab should have the active styling
        $response->assertSee('bg-gray-100', false);
    });

    it('includes project name in breadcrumb', function () {
        $response = $this->get(route('projects.show', $this->project));

        $response->assertStatus(200);
        $response->assertSee($this->project->name);
    });

    it('links to projects index', function () {
        $response = $this->get(route('projects.show', $this->project));

        $response->assertStatus(200);
        $response->assertSee(route('projects.index'));
    });
});

describe('Site Architecture Page Integration', function () {
    it('shows keyboard shortcuts help modal trigger', function () {
        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Keyboard Shortcuts');
    });

    it('shows export button when architecture exists', function () {
        SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Export');
    });

    it('shows crawl button', function () {
        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Start Crawl');
    });

    it('shows re-crawl button when architecture exists', function () {
        SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Re-crawl');
    });

    it('has accessible view mode buttons', function () {
        SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('aria-pressed');
        $response->assertSee('Force Graph view (press 1)');
        $response->assertSee('Tree view (press 2)');
        $response->assertSee('Directory view (press 3)');
    });

    it('has accessible zoom controls', function () {
        SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        // Check for accessible labels (without HTML escaping)
        $response->assertSee('aria-label', false);
        $response->assertSee('Zoom in', false);
        $response->assertSee('Zoom out', false);
        $response->assertSee('Reset view', false);
    });

    it('shows keyboard shortcut hints on toggle options', function () {
        SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $response = $this->get(route('projects.architecture', $this->project));

        $response->assertStatus(200);
        $response->assertSee('(E)');
        $response->assertSee('(C)');
    });
});
