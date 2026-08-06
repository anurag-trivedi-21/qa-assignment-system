<?php

namespace Database\Seeders;

use App\Models\PendingTest;
use App\Models\TestResult;
use App\Models\TestSubject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        User::factory()->create([
            'name' => 'Queue Manager',
            'username' => 'queue-manager',
            'email' => 'manager@example.test',
            'password' => $password,
            'is_enabled' => true,
        ]);

        $testers = User::factory(16)->create([
            'is_tester' => true,
            'is_enabled' => true,
            'password' => $password,
        ]);
        $disabledTesters = collect([
            User::factory()->create([
                'name' => 'Disabled Tester',
                'username' => 'disabled-tester',
                'email' => 'disabled@example.test',
                'is_tester' => true,
                'is_enabled' => false,
                'password' => $password,
            ]),
            ...User::factory(2)->create([
                'is_tester' => true,
                'is_enabled' => false,
                'password' => $password,
            ]),
        ]);

        $categories = ['Application', 'Service', 'Integration', 'Workflow'];
        $subjects = collect(range(1, 55))->map(function (int $number) use ($categories) {
            return TestSubject::query()->create([
                'name' => sprintf('Sample Subject %03d', $number),
                'category' => $categories[($number - 1) % count($categories)],
                'test_value' => 0.5 + (($number - 1) % 6) * 0.5,
            ]);
        });

        foreach ($subjects as $subject) {
            TestResult::factory(fake()->numberBetween(1, 5))->create([
                'test_subject_id' => $subject->id,
                'tester_id' => $testers->random()->id,
            ]);
        }

        // A tester recently handled this subject: it exercises the assignment cooldown rule.
        TestResult::factory()->create([
            'test_subject_id' => $subjects->first()->id,
            'tester_id' => $testers->first()->id,
            'tested_at' => now()->subDays(2),
        ]);

        // Deliberate current-day activity cases for clock-out and assignment behavior.
        // Offset past the cooldown and overloaded personas so each scenario stays observable in isolation.
        collect([
            15,
            45,
            90,
            165,
            195,
            300,
        ])->each(function (int $minutesAgo, int $index) use ($subjects, $testers): void {
            TestResult::factory()->create([
                'test_subject_id' => $subjects[$index + 6]->id,
                'tester_id' => $testers[$index + 6]->id,
                'tested_at' => now()->subMinutes($minutesAgo),
            ]);
        });

        // One tester is intentionally overloaded with active work.
        PendingTest::factory(7)->create([
            'test_subject_id' => fn () => $subjects->first()->id,
            'tester_id' => $testers->first()->id,
            'claimed_at' => now()->subHours(2),
        ]);

        foreach (range(1, 75) as $ignored) {
            PendingTest::factory()->create([
                'test_subject_id' => $subjects->random()->id,
            ]);
        }

        PendingTest::factory()->create([
            'test_subject_id' => $subjects->first()->id,
            'reason' => 'Support',
            'impact_count' => 12000,
        ]);

        // Retain a disabled tester account in history, but it must never receive new work.
        TestResult::factory()->create([
            'test_subject_id' => $subjects->last()->id,
            'tester_id' => $disabledTesters->first()->id,
            'tested_at' => now()->subDay(),
        ]);
    }
}
