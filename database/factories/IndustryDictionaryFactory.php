<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IndustryDictionary>
 */
class IndustryDictionaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'word_count' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Create a medical industry dictionary.
     */
    public function medical(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'medical',
            'name' => 'Medical',
            'description' => 'Medical terminology, drug names, and healthcare terms.',
        ]);
    }

    /**
     * Create a tech industry dictionary.
     */
    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'tech',
            'name' => 'Technology',
            'description' => 'Programming languages, frameworks, and tech terminology.',
        ]);
    }

    /**
     * Create an inactive dictionary.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
