<?php

namespace Database\Factories;

use App\Models\ArchitectureSnapshot;
use App\Models\SiteArchitecture;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchitectureSnapshot>
 */
class ArchitectureSnapshotFactory extends Factory
{
    protected $model = ArchitectureSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nodesCount = fake()->numberBetween(10, 100);
        $linksCount = fake()->numberBetween(20, 200);

        return [
            'site_architecture_id' => SiteArchitecture::factory(),
            'snapshot_data' => $this->generateSnapshotData($nodesCount, $linksCount),
            'nodes_count' => $nodesCount,
            'links_count' => $linksCount,
            'changes_summary' => null,
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function withChanges(): static
    {
        return $this->state(fn (array $attributes) => [
            'changes_summary' => [
                'added' => fake()->numberBetween(1, 10),
                'removed' => fake()->numberBetween(0, 5),
                'changed' => fake()->numberBetween(0, 8),
                'previous_nodes' => fake()->numberBetween(50, 100),
                'current_nodes' => fake()->numberBetween(50, 110),
            ],
        ]);
    }

    public function noChanges(): static
    {
        return $this->state(fn (array $attributes) => [
            'changes_summary' => [
                'added' => 0,
                'removed' => 0,
                'changed' => 0,
                'previous_nodes' => $attributes['nodes_count'] ?? 50,
                'current_nodes' => $attributes['nodes_count'] ?? 50,
            ],
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_data' => [
                'nodes' => [],
                'links' => [],
                'metadata' => [
                    'total_nodes' => 0,
                    'total_links' => 0,
                    'max_depth' => 0,
                    'orphan_count' => 0,
                    'error_count' => 0,
                ],
            ],
            'nodes_count' => 0,
            'links_count' => 0,
        ]);
    }

    /**
     * Generate realistic snapshot data.
     *
     * @return array{nodes: array, links: array, metadata: array}
     */
    protected function generateSnapshotData(int $nodesCount, int $linksCount): array
    {
        $nodes = [];
        $nodeIds = [];

        for ($i = 0; $i < $nodesCount; $i++) {
            $nodeId = Str::uuid()->toString();
            $nodeIds[] = $nodeId;
            $depth = $i === 0 ? 0 : fake()->numberBetween(1, 5);

            $nodes[] = [
                'id' => $nodeId,
                'url' => 'https://example.com/'.fake()->slug(),
                'path' => '/'.fake()->slug(),
                'title' => fake()->sentence(4),
                'status' => 'ok',
                'depth' => $depth,
                'inbound_count' => fake()->numberBetween(0, 20),
                'outbound_count' => fake()->numberBetween(0, 15),
                'link_equity_score' => fake()->randomFloat(6, 0.01, 1.0),
                'is_orphan' => false,
                'is_deep' => $depth > 4,
                'x' => fake()->randomFloat(4, -500, 500),
                'y' => fake()->randomFloat(4, -500, 500),
            ];
        }

        $links = [];
        for ($i = 0; $i < $linksCount && count($nodeIds) >= 2; $i++) {
            $sourceIdx = fake()->numberBetween(0, count($nodeIds) - 1);
            $targetIdx = fake()->numberBetween(0, count($nodeIds) - 1);

            if ($sourceIdx === $targetIdx) {
                $targetIdx = ($targetIdx + 1) % count($nodeIds);
            }

            $links[] = [
                'id' => Str::uuid()->toString(),
                'source' => $nodeIds[$sourceIdx],
                'target' => $nodeIds[$targetIdx],
                'type' => fake()->randomElement(['navigation', 'content', 'footer', 'sidebar']),
                'color' => fake()->hexColor(),
                'anchor_text' => fake()->words(3, true),
                'is_external' => false,
                'is_nofollow' => fake()->boolean(10),
                'position' => fake()->randomElement(['header', 'nav', 'main', 'footer']),
            ];
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'metadata' => [
                'total_nodes' => $nodesCount,
                'total_links' => $linksCount,
                'max_depth' => 5,
                'orphan_count' => fake()->numberBetween(0, 3),
                'error_count' => fake()->numberBetween(0, 2),
            ],
        ];
    }
}
