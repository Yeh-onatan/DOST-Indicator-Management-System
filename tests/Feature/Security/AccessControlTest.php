<?php

use App\Models\User;
use function Pest\Laravel\{actingAs, get};

// --- AUTHENTICATION BYPASS TESTING ---
test('guests are redirected to login', function () {
    get('/home')->assertRedirect(route('login'));
    get('/admin/manage')->assertRedirect(route('login'));
    get('/settings/profile')->assertRedirect(route('login'));
});

// --- ROLE ESCALATION (PEN TESTING) ---
// Test that a "Proponent" cannot access Admin routes
test('VUL-01: Proponents cannot access admin settings', function (string $url) {
    $user = User::factory()->create(['role' => 'proponent']);

    actingAs($user)
        ->get($url)
        ->assertForbidden(); // Expects 403 Forbidden
})->with([
    '/settings/admin/indicators',
    '/settings/admin/reporting',
    '/settings/admin/approvals',
    '/admin/manage',
    '/settings/org', // Super Admin only
]);

// --- SUPER ADMIN EXCLUSIVE ---
// Test that a regular "Administrator" cannot access Super Admin routes
test('VUL-02: Administrators cannot access super admin audit logs', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    actingAs($admin)
        ->get('/settings/audit')
        ->assertForbidden();
});

// --- WHITE BOX: ROLE CHECK METHODS ---
test('WB-01: User model correctly identifies roles', function () {
    $admin = User::factory()->make(['role' => 'administrator']);
    expect($admin->isAdministrator())->toBeTrue()
        ->and($admin->isSuperAdmin())->toBeFalse();

    $ro = User::factory()->make(['role' => 'ro']);
    expect($ro->isRO())->toBeTrue()
        ->and($ro->isPSTO())->toBeFalse();
});