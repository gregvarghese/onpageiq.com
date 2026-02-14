<?php

use App\Models\Project;
use App\Models\ScanSchedule;
use App\Models\UrlGroup;

test('scan schedule belongs to project', function () {
    $schedule = ScanSchedule::factory()->create();

    expect($schedule->project)->toBeInstanceOf(Project::class);
});

test('scan schedule can belong to url group', function () {
    $urlGroup = UrlGroup::factory()->create();
    $schedule = ScanSchedule::factory()->create([
        'url_group_id' => $urlGroup->id,
    ]);

    expect($schedule->urlGroup)->toBeInstanceOf(UrlGroup::class);
    expect($schedule->urlGroup->id)->toBe($urlGroup->id);
});

test('scan schedule has required fields', function () {
    $schedule = ScanSchedule::factory()->create([
        'frequency' => 'daily',
        'scan_type' => 'quick',
        'is_active' => true,
    ]);

    expect($schedule->frequency)->toBe('daily');
    expect($schedule->scan_type)->toBe('quick');
    expect($schedule->is_active)->toBeTrue();
});

test('scan schedule can be deactivated', function () {
    $schedule = ScanSchedule::factory()->create([
        'is_active' => true,
    ]);

    $schedule->update([
        'is_active' => false,
        'deactivated_at' => now(),
        'deactivation_reason' => 'insufficient_credits',
    ]);

    expect($schedule->fresh()->is_active)->toBeFalse();
    expect($schedule->fresh()->deactivation_reason)->toBe('insufficient_credits');
});

test('scan schedule stores metadata as array', function () {
    $metadata = ['consecutive_credit_failures' => 3];
    $schedule = ScanSchedule::factory()->create([
        'metadata' => $metadata,
    ]);

    expect($schedule->metadata)->toBeArray();
    expect($schedule->metadata['consecutive_credit_failures'])->toBe(3);
});
