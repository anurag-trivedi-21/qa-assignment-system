<?php

namespace App\Models;

use Database\Factories\PendingTestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingTest extends Model
{
    /** @use HasFactory<PendingTestFactory> */
    use HasFactory;

    protected $fillable = [
        'test_subject_id', 'tester_id', 'reason', 'impact_count', 'claimed_at',
    ];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime'];
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
