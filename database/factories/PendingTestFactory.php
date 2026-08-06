<?php

namespace Database\Factories;

use App\Models\PendingTest;
use App\Models\TestSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PendingTest> */
class PendingTestFactory extends Factory
{
    protected $model = PendingTest::class;

    public function definition(): array
    {
        return [
            'test_subject_id' => TestSubject::factory(),
            'reason' => fake()->randomElement(['Regression', 'Customer Report', 'New Subject', 'Support']),
            'impact_count' => fake()->numberBetween(25, 8000),
        ];
    }
}
