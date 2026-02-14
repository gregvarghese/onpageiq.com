<?php

use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\User;

test('issue assignment belongs to issue', function () {
    $assignment = IssueAssignment::factory()->create();

    expect($assignment->issue)->toBeInstanceOf(Issue::class);
});

test('issue assignment belongs to assigned user', function () {
    $user = User::factory()->create();
    $assignment = IssueAssignment::factory()->create([
        'assigned_to_user_id' => $user->id,
    ]);

    expect($assignment->assignedTo)->toBeInstanceOf(User::class);
    expect($assignment->assignedTo->id)->toBe($user->id);
});

test('issue assignment belongs to assigning user', function () {
    $user = User::factory()->create();
    $assignment = IssueAssignment::factory()->create([
        'assigned_by_user_id' => $user->id,
    ]);

    expect($assignment->assignedBy)->toBeInstanceOf(User::class);
    expect($assignment->assignedBy->id)->toBe($user->id);
});

test('issue assignment has default status of open', function () {
    $assignment = IssueAssignment::factory()->create();

    expect($assignment->status)->toBe('open');
});

test('issue assignment can be marked as resolved', function () {
    $assignment = IssueAssignment::factory()->create([
        'status' => 'open',
    ]);

    $assignment->update([
        'status' => 'resolved',
        'resolved_at' => now(),
    ]);

    expect($assignment->fresh()->status)->toBe('resolved');
    expect($assignment->fresh()->resolved_at)->not->toBeNull();
});

test('issue assignment tracks timestamps', function () {
    $assignment = IssueAssignment::factory()->create();

    expect($assignment->created_at)->not->toBeNull();
    expect($assignment->updated_at)->not->toBeNull();
});
