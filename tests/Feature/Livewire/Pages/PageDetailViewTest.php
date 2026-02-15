<?php

use App\Livewire\Pages\PageDetailView;
use App\Models\ArchitectureNode;
use App\Models\Organization;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\Url;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'pro']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->url = Url::factory()->for($this->project)->create([
        'url' => 'https://example.com/test-page',
    ]);
    $this->actingAs($this->user);
});

it('renders the page detail view', function () {
    Livewire::test(PageDetailView::class, ['url' => $this->url])
        ->assertStatus(200)
        ->assertSee('Core Web Vitals');
});

it('returns null architecture node when no architecture exists', function () {
    $component = Livewire::test(PageDetailView::class, ['url' => $this->url]);

    expect($component->instance()->architectureNode)->toBeNull();
});

it('returns architecture node when page exists in architecture', function () {
    // Create architecture with a node matching this URL
    $architecture = SiteArchitecture::factory()
        ->for($this->project)
        ->create();

    $node = ArchitectureNode::factory()
        ->for($architecture, 'siteArchitecture')
        ->create([
            'url' => $this->url->url,
        ]);

    $component = Livewire::test(PageDetailView::class, ['url' => $this->url]);

    expect($component->instance()->architectureNode)->not->toBeNull();
    expect($component->instance()->architectureNode->id)->toBe($node->id);
});

it('returns null architecture node when URL is not in architecture', function () {
    // Create architecture without matching node
    $architecture = SiteArchitecture::factory()
        ->for($this->project)
        ->create();

    ArchitectureNode::factory()
        ->for($architecture, 'siteArchitecture')
        ->create([
            'url' => 'https://example.com/different-page',
        ]);

    $component = Livewire::test(PageDetailView::class, ['url' => $this->url]);

    expect($component->instance()->architectureNode)->toBeNull();
});

it('displays core web vitals section', function () {
    Livewire::test(PageDetailView::class, ['url' => $this->url])
        ->assertSee('Core Web Vitals')
        ->assertSee('LCP')
        ->assertSee('FID')
        ->assertSee('CLS');
});

it('displays issues section', function () {
    Livewire::test(PageDetailView::class, ['url' => $this->url])
        ->assertSee('Issues');
});

it('requires authorization to view', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser);

    Livewire::test(PageDetailView::class, ['url' => $this->url])
        ->assertForbidden();
});
