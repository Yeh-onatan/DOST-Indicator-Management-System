<?php

use App\Models\User;
use App\Models\Objective;
use App\Livewire\Dashboard\UnifiedDashboard;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class); // This wipes the DB before every test

beforeEach(function () {
    $this->seed(\Database\Seeders\IndicatorCategorySeeder::class);
    $this->seed(\Database\Seeders\CategoryFieldSeeder::class);
});

// --- BLACK BOX: FUNCTIONALITY ---
test('BB-01: Dashboard loads and renders objectives', function () {
    $user = User::factory()->create();
    Objective::factory()->count(3)->create(['submitted_by_user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->assertStatus(200)
        ->assertSee('Create Indicator'); // Check for UI elements
});

test('BB-02: Filtering by Year updates the list', function () {
    $user = User::factory()->create(['role' => 'administrator']);
    
    // Create data for 2024 and 2025
    Objective::factory()->create(['target_period' => '2024', 'indicator' => 'Target 2024']);
    Objective::factory()->create(['target_period' => '2025', 'indicator' => 'Target 2025']);

    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->set('yearFilter', 2024) // Simulate user selecting dropdown
        ->assertSee('Target 2024')
        ->assertDontSee('Target 2025');
});

// --- NEGATIVE TESTING (INPUT VALIDATION) ---
test('NEG-01: Quick Create Form requires mandatory fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->call('openQuickForm')
        ->set('quickForm.indicator', '') // Empty Indicator
        ->set('quickForm.year_start', '') // Empty Year
        ->call('saveQuickForm')
        ->assertHasErrors(['quickForm.indicator', 'quickForm.year_start']);
});

test('NEG-02: End Year cannot be before Start Year', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->call('openQuickForm')
        ->set('quickForm.year_start', 2025)
        ->set('quickForm.year_end', 2020) // Invalid Range
        ->call('saveQuickForm')
        ->assertHasErrors(['quickForm.year_end']);
});