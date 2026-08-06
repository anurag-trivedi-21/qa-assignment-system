<?php

use App\Models\User;

it('forbids testers from viewing the users resource', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);

    $this->actingAs($tester)->get('/admin/users')->assertForbidden();
});

it('allows managers to view the users resource', function () {
    $manager = User::factory()->create(['is_tester' => false, 'is_enabled' => true]);

    $this->actingAs($manager)->get('/admin/users')->assertOk();
});
