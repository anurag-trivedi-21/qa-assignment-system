<?php

namespace Database\Factories;

use App\Models\TestSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestSubject> */
class TestSubjectFactory extends Factory
{
    protected $model = TestSubject::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'category' => fake()->randomElement(['Application', 'Service', 'Integration']),
            'test_value' => fake()->randomFloat(2, 0.5, 3.0),
        ];
    }
}
