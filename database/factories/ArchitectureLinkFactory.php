<?php

namespace Database\Factories;

use App\Enums\LinkType;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchitectureLink>
 */
class ArchitectureLinkFactory extends Factory
{
    protected $model = ArchitectureLink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_architecture_id' => SiteArchitecture::factory(),
            'source_node_id' => ArchitectureNode::factory(),
            'target_node_id' => ArchitectureNode::factory(),
            'target_url' => fake()->url(),
            'link_type' => fake()->randomElement(LinkType::cases()),
            'link_type_override' => null,
            'anchor_text' => fake()->words(fake()->numberBetween(1, 5), true),
            'is_external' => false,
            'external_domain' => null,
            'is_nofollow' => fake()->boolean(10),
            'position_in_page' => fake()->randomElement(['header', 'nav', 'main', 'sidebar', 'footer']),
            'created_at' => now(),
        ];
    }

    public function navigation(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Navigation,
            'position_in_page' => 'nav',
        ]);
    }

    public function content(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Content,
            'position_in_page' => 'main',
        ]);
    }

    public function footer(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Footer,
            'position_in_page' => 'footer',
        ]);
    }

    public function sidebar(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Sidebar,
            'position_in_page' => 'sidebar',
        ]);
    }

    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Header,
            'position_in_page' => 'header',
        ]);
    }

    public function breadcrumb(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Breadcrumb,
            'position_in_page' => 'main',
        ]);
    }

    public function pagination(): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::Pagination,
            'position_in_page' => 'main',
            'anchor_text' => fake()->randomElement(['Next', 'Previous', '1', '2', '3', '»', '«']),
        ]);
    }

    public function external(): static
    {
        $domain = fake()->domainName();

        return $this->state(fn (array $attributes) => [
            'link_type' => LinkType::External,
            'target_node_id' => null,
            'target_url' => 'https://'.$domain.'/'.fake()->slug(),
            'is_external' => true,
            'external_domain' => $domain,
        ]);
    }

    public function broken(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_node_id' => null,
            'is_external' => false,
        ]);
    }

    public function nofollow(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_nofollow' => true,
        ]);
    }

    public function withOverride(LinkType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'link_type_override' => $type,
        ]);
    }
}
