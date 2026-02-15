<?php

namespace Database\Factories;

use App\Enums\NodeStatus;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchitectureNode>
 */
class ArchitectureNodeFactory extends Factory
{
    protected $model = ArchitectureNode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $path = '/'.fake()->slug(fake()->numberBetween(1, 4));
        $domain = fake()->domainName();

        return [
            'site_architecture_id' => SiteArchitecture::factory(),
            'url' => 'https://'.$domain.$path,
            'path' => $path,
            'title' => fake()->sentence(fake()->numberBetween(3, 8)),
            'status' => NodeStatus::Ok,
            'http_status' => 200,
            'depth' => fake()->numberBetween(0, 5),
            'inbound_count' => fake()->numberBetween(1, 50),
            'outbound_count' => fake()->numberBetween(0, 30),
            'link_equity_score' => fake()->randomFloat(6, 0.001, 1.0),
            'word_count' => fake()->numberBetween(100, 5000),
            'issues_count' => 0,
            'is_orphan' => false,
            'is_deep' => false,
            'metadata' => [
                'meta_description' => fake()->sentence(),
                'h1' => fake()->sentence(4),
            ],
            'position_x' => fake()->randomFloat(4, -500, 500),
            'position_y' => fake()->randomFloat(4, -500, 500),
        ];
    }

    public function homepage(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => '/',
            'title' => 'Homepage',
            'depth' => 0,
            'inbound_count' => 0,
            'outbound_count' => fake()->numberBetween(10, 50),
            'link_equity_score' => 1.0,
            'is_orphan' => false,
            'is_deep' => false,
        ]);
    }

    public function orphan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NodeStatus::Orphan,
            'is_orphan' => true,
            'inbound_count' => 0,
            'link_equity_score' => 0.0,
            'issues_count' => 1,
            'depth' => fake()->numberBetween(1, 5), // Non-zero to avoid being treated as homepage
        ]);
    }

    public function deep(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NodeStatus::Deep,
            'depth' => fake()->numberBetween(5, 10),
            'is_deep' => true,
            'issues_count' => 1,
        ]);
    }

    public function redirect(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NodeStatus::Redirect,
            'http_status' => fake()->randomElement([301, 302, 307, 308]),
        ]);
    }

    public function clientError(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NodeStatus::ClientError,
            'http_status' => fake()->randomElement([400, 401, 403, 404, 410]),
            'issues_count' => 1,
        ]);
    }

    public function serverError(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NodeStatus::ServerError,
            'http_status' => fake()->randomElement([500, 502, 503, 504]),
            'issues_count' => 1,
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NodeStatus::Timeout,
            'http_status' => null,
            'issues_count' => 1,
        ]);
    }

    public function withHighEquity(): static
    {
        return $this->state(fn (array $attributes) => [
            'inbound_count' => fake()->numberBetween(50, 200),
            'link_equity_score' => fake()->randomFloat(6, 0.7, 1.0),
        ]);
    }

    public function withLowEquity(): static
    {
        return $this->state(fn (array $attributes) => [
            'inbound_count' => fake()->numberBetween(1, 5),
            'link_equity_score' => fake()->randomFloat(6, 0.001, 0.1),
        ]);
    }
}
