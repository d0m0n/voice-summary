<?php

use App\Models\Meeting;
use App\Models\User;

test('admin can create meeting', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)->post('/meetings', [
        'name' => 'Test Meeting',
        'description' => 'Test Description',
        'language' => 'ja-JP'
    ]);

    $response->assertRedirect('/meetings');
    $this->assertDatabaseHas('meetings', [
        'name' => 'Test Meeting',
        'created_by' => $admin->id
    ]);
});

test('viewer cannot create meeting', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    
    $response = $this->actingAs($viewer)->post('/meetings', [
        'name' => 'Test Meeting',
        'description' => 'Test Description',
        'language' => 'ja-JP'
    ]);

    $response->assertStatus(403);
});

test('all users can view meetings', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $meeting = Meeting::factory()->create();
    
    $response = $this->actingAs($viewer)->get('/meetings');

    $response->assertStatus(200);
    $response->assertSee($meeting->name);
});

test('all users can view meeting details', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $meeting = Meeting::factory()->create();
    
    $response = $this->actingAs($viewer)->get("/meetings/{$meeting->id}");

    $response->assertStatus(200);
    $response->assertSee($meeting->name);
});
