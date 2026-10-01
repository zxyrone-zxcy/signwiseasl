<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\UserProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $lessons = Lesson::query()->orderBy('id')->get();
        $progress = $user->progress()->with('lesson')->latest('updated_at')->get();
        $completedCount = $user->progress()->where('is_completed', true)->count();
        $lessonCount = $lessons->count();

        return view('dashboard', [
            'lessons' => $lessons,
            'progress' => $progress,
            'completedCount' => $completedCount,
            'lessonCount' => $lessonCount,
            'completionRate' => $lessonCount === 0 ? 0 : (int) round($completedCount / $lessonCount * 100),
            'averageScore' => $user->progress()->whereNotNull('quiz_score')->avg('quiz_score'),
        ]);
    }

    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        $request->user()->progress()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            ['is_completed' => true, 'last_practiced_at' => now()],
        );

        return redirect()->route('lessons.show', $lesson)->with('status', 'Practice attempt saved to your progress.');
    }

    public function quiz(): View
    {
        $questions = Lesson::query()->inRandomOrder()->limit(5)->get()->map(function (Lesson $lesson): array {
            return [
                'lesson' => $lesson,
                'choices' => Lesson::query()
                    ->whereKeyNot($lesson->id)
                    ->inRandomOrder()
                    ->limit(3)
                    ->get()
                    ->push($lesson)
                    ->shuffle(),
            ];
        });

        session(['quiz.answer_key' => $questions->mapWithKeys(
            fn (array $question): array => [$question['lesson']->id => $question['lesson']->id],
        )->all()]);

        return view('quiz', ['questions' => $questions]);
    }

    public function submitQuiz(Request $request): RedirectResponse
    {
        $answers = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'distinct'],
        ])['answers'];
        $answerKey = session()->pull('quiz.answer_key');

        if (! is_array($answerKey) || count($answerKey) === 0) {
            throw ValidationException::withMessages(['answers' => 'Start a new practice quiz before submitting.']);
        }

        $allowedIds = Lesson::query()->whereKey($answerKey)->pluck('id')->all();
        $correctCount = 0;

        foreach ($answerKey as $lessonId => $correctId) {
            $isCorrect = isset($answers[$lessonId])
                && (int) $answers[$lessonId] === (int) $correctId
                && in_array((int) $answers[$lessonId], $allowedIds, true);
            $correctCount += (int) $isCorrect;

            UserProgress::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'lesson_id' => $lessonId],
                ['quiz_score' => $isCorrect ? 100 : 0, 'last_practiced_at' => now()],
            );
        }

        return redirect()->route('dashboard')->with('status', "Quiz saved: {$correctCount} of ".count($answerKey).' correct.');
    }
}
