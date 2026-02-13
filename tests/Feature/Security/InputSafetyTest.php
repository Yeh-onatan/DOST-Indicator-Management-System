<?php

use App\Models\User;
use App\Models\Objective;
use Livewire\Livewire;
use App\Livewire\Dashboard\UnifiedDashboard;

test('VUL-03: XSS payload in Indicator Name is sanitized', function () {
    $user = User::factory()->create();
    $xss = "<script>alert('HACKED')</script>";

    // Inject XSS via Livewire component
    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->call('openQuickForm')
        ->set('quickForm.indicator', $xss)
        ->set('quickForm.year_start', 2025)
        // ... fill other required fields ...
        ->set('quickForm.category', 'strategic_plan') 
        ->call('saveQuickForm');

    // Retrieve and check
    $obj = Objective::where('indicator', $xss)->first();
    
    // The database SHOULD store it as is (raw), but the VIEW must escape it.
    // We check that the Livewire component does not render the raw script tags unescaped.
    Livewire::actingAs($user)
        ->test(UnifiedDashboard::class)
        ->assertSeeHtml(e($xss)); // Expecting the ESCAPED version (&lt;script&gt;)
});