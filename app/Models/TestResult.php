<?php

namespace App\Models;

use Database\Factories\TestResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    /** @use HasFactory<TestResultFactory> */
    use HasFactory;

    protected $fillable = ['test_subject_id', 'tester_id', 'result', 'tested_at', 'notes'];

    protected function casts(): array
    {
        return ['tested_at' => 'datetime'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(TestSubject::class, 'test_subject_id');
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tester_id');
    }
}
