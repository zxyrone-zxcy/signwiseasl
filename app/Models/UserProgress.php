<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'is_completed',
        'quiz_score',
        'best_accuracy',
        'last_accuracy',
        'last_practiced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'last_practiced_at' => 'datetime',
        ];
    }

    /**
     * Store the latest gesture accuracy (0-100) and keep the personal best.
     */
    public function recordAccuracy(int $accuracy): void
    {
        $this->last_accuracy = $accuracy;
        $this->best_accuracy = max((int) $this->best_accuracy, $accuracy);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
