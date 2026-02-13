<?php

use App\Models\User;
use App\Models\Objective;
use Livewire\Livewire;
use App\Livewire\Dashboard\UnifiedDashboard;

test('STR-01: Dashboard handles 100+ records without crashing', function () {
    $user = User::factory()->create(['role' => 'administrator']);
    
    // Seed 100 records
    Objective::factory()->count(100)->create([
        'target_period' => '2025-2030',
        'office_id' => $user->office_id
    ]);

    $start = microtime(true);

    // Render component
    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('objectives'); // Pagination should handle this

    $duration = microtime(true) - $start;

    // Assert it loads within acceptable time (e.g., < 2 seconds for PHP processing)
    expect($duration)->toBeLessThan(2.0);
});

test('STR-02: Bulk Import Stress Test (500 rows)', function () {
    $user = User::factory()->create(['role' => 'administrator']);
    
    // Generate large CSV content in memory
    $header = "Category,Indicator,Year,Target,Baseline,MOV,Responsible Agency,Reporting Agency,Operational Definition";
    $rows = [];
    for ($i = 0; $i < 500; $i++) {
        $rows[] = "strategic_plan,Indicator $i,2025,100,0,Docs,Agency,Agency,Def";
    }
    $csvContent = $header . "\n" . implode("\n", $rows);
    
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('bulk_import.csv', $csvContent);

    // Attempt import
    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->set('importFile', $file)
        ->call('importIndicators')
        ->assertDispatched('toast', type: 'success');

    // Verify 500 records created
    expect(Objective::count())->toBeGreaterThanOrEqual(500);
});