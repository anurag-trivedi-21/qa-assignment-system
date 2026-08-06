<?php

namespace Database\Factories;

use App\Models\TestResult;
use App\Models\TestSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestResult> */
class TestResultFactory extends Factory
{
    protected $model = TestResult::class;

    public function definition(): array
    {
        return [
            'test_subject_id' => TestSubject::factory(),
            'tester_id' => User::factory(),
            'result' => fake()->randomElement(['passed', 'failed']),
            'tested_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
