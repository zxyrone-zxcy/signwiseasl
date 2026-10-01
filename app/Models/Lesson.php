<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'difficulty',
        'duration_minutes',
        'description',
        'sign_reference',
        'practice_tip',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }
}
