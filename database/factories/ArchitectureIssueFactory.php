<?php

namespace Database\Factories;

use App\Enums\ArchitectureIssueType;
use App\Enums\ImpactLevel;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchitectureIssue>
 */
class ArchitectureIssueFactory extends Factory
{
    protected $model = ArchitectureIssue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueType = fake()->randomElement(ArchitectureIssueType::cases());

        return [
            'site_architecture_id' => SiteArchitecture::factory(),
            'node_id' => ArchitectureNode::factory(),
            'issue_type' => $issueType,
            'severity' => $issueType->severity(),
            'message' => $issueType->description(),
            'recommendation' => fake()->sentence(),
            'is_resolved' => false,
        ];
    }

    public function orphanPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'issue_type' => ArchitectureIssueType::OrphanPage,
            'severity' => ImpactLevel::Serious,
            'message' => ArchitectureIssueType::OrphanPage->description(),
            'recommendation' => 'Add internal links from other relevant pages to this page to improve discoverability.',
        ]);
    }

    public function deepPage(): static
    {
        $depth = fake()->numberBetween(5, 10);

        return $this->state(fn (array $attributes) => [
            'issue_type' => ArchitectureIssueType::DeepPage,
            'severity' => ImpactLevel::Moderate,
            'message' => ArchitectureIssueType::DeepPage->description(),
            'recommendation' => "This page is {$depth} clicks from the homepage. Consider adding direct links from higher-level pages.",
        ]);
    }

    public function brokenLink(): static
    {
        $brokenUrl = fake()->url();

        return $this->state(fn (array $attributes) => [
            'issue_type' => ArchitectureIssueType::BrokenLink,
            'severity' => ImpactLevel::Critical,
            'message' => "Page contains a broken link to: {$brokenUrl}",
            'recommendation' => 'Remove or update the broken link to point to a valid URL.',
        ]);
    }

    public function redirectChain(): static
    {
        return $this->state(fn (array $attributes) => [
            'issue_type' => ArchitectureIssueType::RedirectChain,
            'severity' => ImpactLevel::Moderate,
            'message' => ArchitectureIssueType::RedirectChain->description(),
            'recommendation' => 'Update links to point directly to the final destination URL.',
        ]);
    }

    public function thinContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'issue_type' => ArchitectureIssueType::ThinContent,
            'severity' => ImpactLevel::Minor,
            'message' => ArchitectureIssueType::ThinContent->description(),
            'recommendation' => 'Consider expanding the content or consolidating with related pages.',
        ]);
    }

    public function duplicateContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'issue_type' => ArchitectureIssueType::DuplicateContent,
            'severity' => ImpactLevel::Serious,
            'message' => ArchitectureIssueType::DuplicateContent->description(),
            'recommendation' => 'Use canonical tags or consolidate duplicate pages.',
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => ImpactLevel::Critical,
        ]);
    }

    public function serious(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => ImpactLevel::Serious,
        ]);
    }

    public function moderate(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => ImpactLevel::Moderate,
        ]);
    }

    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => ImpactLevel::Minor,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
        ]);
    }

    public function unresolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => false,
        ]);
    }
}
