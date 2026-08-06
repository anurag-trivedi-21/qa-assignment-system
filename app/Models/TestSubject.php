<?php

namespace App\Models;

use Database\Factories\TestSubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestSubject extends Model
{
    /** @use HasFactory<TestSubjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'category', 'test_value'];

    protected function casts(): array
    {
        return ['test_value' => 'float'];
    }

    public function pendingTests(): HasMany
    {
        return $this->hasMany(PendingTest::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }
}
