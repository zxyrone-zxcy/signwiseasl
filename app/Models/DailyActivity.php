<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class DailyActivity extends Model
{
    protected $fillable = [
        'user_id',
        'activity_date',
        'lessons_practiced',
        'quizzes_taken',
        'quiz_correct',
        'quiz_total',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add to today's tally for a learner, creating the day's row on first activity.
     *
     * @param  array{lessons_practiced?: int, quizzes_taken?: int, quiz_correct?: int, quiz_total?: int}  $increments
     */
    public static function recordFor(User $user, array $increments): self
    {
        $activity = static::query()->firstOrCreate([
            'user_id' => $user->id,
            'activity_date' => today()->toDateString(),
        ]);

        foreach ($increments as $column => $amount) {
            $activity->increment($column, $amount);
        }

        return $activity->refresh();
    }

    /**
     * Consecutive days of activity. A streak stays alive through today
     * if the learner was last active yesterday.
     *
     * @param  Collection<int, string>  $activeDates  Y-m-d strings
     */
    public static function streakFrom($activeDates): int
    {
        $days = $activeDates->flip();
        $cursor = today();

        if (! $days->has($cursor->toDateString())) {
            $cursor = $cursor->copy()->subDay();
        }

        $streak = 0;

        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->copy()->subDay();
        }

        return $streak;
    }
}
