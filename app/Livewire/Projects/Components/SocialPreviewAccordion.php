<?php

namespace App\Livewire\Projects\Components;

use App\Models\Url;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SocialPreviewAccordion extends Component
{
    public Url $url;

    public bool $expanded = true;

    public bool $generatingSuggestions = false;

    public function mount(Url $url): void
    {
        $this->url = $url;
    }

    /**
     * Toggle accordion expanded state.
     */
    public function toggle(): void
    {
        $this->expanded = ! $this->expanded;
    }

    /**
     * Get the meta data for this URL from the latest scan.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function metaData(): array
    {
        $scan = $this->url->latestCompletedScan;
        $metadata = $scan?->result?->metadata ?? [];

        return [
            'title' => $metadata['title'] ?? '',
            'description' => $metadata['meta_description'] ?? '',
            'canonical' => $metadata['canonical'] ?? $this->url->url,
            'og' => [
                'title' => $metadata['og_title'] ?? $metadata['title'] ?? '',
                'description' => $metadata['og_description'] ?? $metadata['meta_description'] ?? '',
                'image' => $metadata['og_image'] ?? null,
                'type' => $metadata['og_type'] ?? 'website',
                'site_name' => $metadata['og_site_name'] ?? '',
            ],
            'twitter' => [
                'card' => $metadata['twitter_card'] ?? 'summary',
                'title' => $metadata['twitter_title'] ?? $metadata['og_title'] ?? $metadata['title'] ?? '',
                'description' => $metadata['twitter_description'] ?? $metadata['og_description'] ?? $metadata['meta_description'] ?? '',
                'image' => $metadata['twitter_image'] ?? $metadata['og_image'] ?? null,
                'site' => $metadata['twitter_site'] ?? '',
            ],
        ];
    }

    /**
     * Get validation warnings for each platform.
     *
     * @return array<string, array<string>>
     */
    #[Computed]
    public function validationWarnings(): array
    {
        $meta = $this->metaData;
        $warnings = [
            'google' => [],
            'facebook' => [],
            'twitter' => [],
            'linkedin' => [],
        ];

        // Google/SERP validations
        if (empty($meta['title'])) {
            $warnings['google'][] = 'Missing page title';
        } elseif (strlen($meta['title']) > 60) {
            $warnings['google'][] = 'Title exceeds 60 characters (may be truncated)';
        } elseif (strlen($meta['title']) < 30) {
            $warnings['google'][] = 'Title is quite short (under 30 characters)';
        }

        if (empty($meta['description'])) {
            $warnings['google'][] = 'Missing meta description';
        } elseif (strlen($meta['description']) > 160) {
            $warnings['google'][] = 'Description exceeds 160 characters (may be truncated)';
        } elseif (strlen($meta['description']) < 70) {
            $warnings['google'][] = 'Description is quite short (under 70 characters)';
        }

        // Facebook/OG validations
        if (empty($meta['og']['title'])) {
            $warnings['facebook'][] = 'Missing og:title tag';
        } elseif (strlen($meta['og']['title']) > 60) {
            $warnings['facebook'][] = 'OG title exceeds 60 characters';
        }

        if (empty($meta['og']['description'])) {
            $warnings['facebook'][] = 'Missing og:description tag';
        }

        if (empty($meta['og']['image'])) {
            $warnings['facebook'][] = 'Missing og:image (posts without images get less engagement)';
        }

        // Twitter validations
        if (empty($meta['twitter']['card'])) {
            $warnings['twitter'][] = 'Missing twitter:card tag';
        }

        if (empty($meta['twitter']['title']) && empty($meta['og']['title'])) {
            $warnings['twitter'][] = 'Missing twitter:title (and no og:title fallback)';
        }

        if (empty($meta['twitter']['image']) && empty($meta['og']['image'])) {
            $warnings['twitter'][] = 'Missing twitter:image (and no og:image fallback)';
        }

        // LinkedIn validations (uses OG tags)
        if (empty($meta['og']['title'])) {
            $warnings['linkedin'][] = 'Missing og:title tag';
        }

        if (empty($meta['og']['image'])) {
            $warnings['linkedin'][] = 'Missing og:image (LinkedIn requires images for rich previews)';
        }

        if (empty($meta['og']['description'])) {
            $warnings['linkedin'][] = 'Missing og:description tag';
        }

        return $warnings;
    }

    /**
     * Get total warning count.
     */
    #[Computed]
    public function totalWarnings(): int
    {
        return collect($this->validationWarnings)->flatten()->count();
    }

    /**
     * Generate AI suggestions for meta tags.
     */
    public function generateSuggestions(): void
    {
        $this->generatingSuggestions = true;

        // Check if organization has credits
        $organization = $this->url->project->organization;

        if (! $organization->hasCreditsFor('meta_suggestion')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Insufficient credits for AI suggestions.',
            ]);
            $this->generatingSuggestions = false;

            return;
        }

        // Dispatch the job to generate suggestions
        // This would typically dispatch a job that uses AI to generate suggestions
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Generating suggestions... This may take a moment.',
        ]);

        // For now, we'll simulate the completion
        // In production, this would dispatch GenerateMetaSuggestionsJob
        $this->generatingSuggestions = false;
    }

    public function render(): View
    {
        return view('livewire.projects.components.social-preview-accordion');
    }
}
