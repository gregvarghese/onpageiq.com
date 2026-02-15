<?php

namespace Database\Factories;

use App\Enums\EvidenceType;
use App\Models\AuditCheck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditEvidence>
 */
class AuditEvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_check_id' => AuditCheck::factory(),
            'captured_by_user_id' => User::factory(),
            'type' => EvidenceType::Note,
            'file_path' => null,
            'external_url' => null,
            'notes' => fake()->paragraph(),
            'title' => fake()->sentence(3),
            'mime_type' => null,
            'file_size' => null,
            'captured_at' => now(),
        ];
    }

    /**
     * Create evidence for a specific check.
     */
    public function forCheck(AuditCheck $check): static
    {
        return $this->state(fn (array $attributes) => [
            'audit_check_id' => $check->id,
        ]);
    }

    /**
     * Create evidence captured by a specific user.
     */
    public function capturedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'captured_by_user_id' => $user->id,
        ]);
    }

    /**
     * Create a screenshot evidence.
     */
    public function screenshot(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EvidenceType::Screenshot,
            'file_path' => 'evidence/screenshots/'.fake()->uuid().'.png',
            'mime_type' => 'image/png',
            'file_size' => fake()->numberBetween(50000, 500000),
            'title' => 'Screenshot - '.fake()->words(2, true),
            'notes' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Create a recording evidence.
     */
    public function recording(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EvidenceType::Recording,
            'file_path' => 'evidence/recordings/'.fake()->uuid().'.webm',
            'mime_type' => 'video/webm',
            'file_size' => fake()->numberBetween(1000000, 10000000),
            'title' => 'Screen Recording - '.fake()->words(2, true),
            'notes' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Create a note evidence.
     */
    public function note(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EvidenceType::Note,
            'file_path' => null,
            'mime_type' => null,
            'file_size' => null,
            'title' => 'Note - '.fake()->words(2, true),
            'notes' => fake()->paragraphs(2, true),
        ]);
    }

    /**
     * Create a link evidence.
     */
    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EvidenceType::Link,
            'file_path' => null,
            'external_url' => fake()->url(),
            'mime_type' => null,
            'file_size' => null,
            'title' => 'External Resource',
            'notes' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Create a document evidence.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EvidenceType::Document,
            'file_path' => 'evidence/documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 2000000),
            'title' => 'Document - '.fake()->words(2, true),
            'notes' => fake()->optional()->sentence(),
        ]);
    }
}
