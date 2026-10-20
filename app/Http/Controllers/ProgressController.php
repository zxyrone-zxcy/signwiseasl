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
        session()->forget('quiz_result');

        // Camera quiz: each question is just a lesson. The learner signs it and the browser reports pass/fail.
        $questions = Lesson::query()->inRandomOrder()->limit(5)->get()->map(
            fn (Lesson $lesson): array => ['lesson' => $lesson],
        );

        session(['quiz.answer_key' => $questions->mapWithKeys(
            fn (array $question): array => [$question['lesson']->id => $question['lesson']->id],
        )->all()]);

        return view('quiz', ['questions' => $questions]);
    }

    public function results(Request $request): View|RedirectResponse
    {
        $result = $request->session()->get('quiz_result');

        if (! is_array($result)) {
            return redirect()->route('quiz');
        }

        return view('quiz-results', ['result' => $result]);
    }

    public function submitQuiz(Request $request): RedirectResponse
    {
        // results[lessonId] is "1" (passed) or "0" (failed or skipped), set by the camera quiz in the browser.
        $results = $request->validate([
            'results' => ['required', 'array'],
            'results.*' => ['required', 'boolean'],
        ])['results'];

        $answerKey = session()->pull('quiz.answer_key');

        if (! is_array($answerKey) || count($answerKey) === 0) {
            throw ValidationException::withMessages(['results' => 'Start a new practice quiz before submitting.']);
        }

        $correctCount = 0;

        foreach ($answerKey as $lessonId => $unused) {
            $isCorrect = (bool) ($results[$lessonId] ?? false);
            $correctCount += (int) $isCorrect;

            UserProgress::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'lesson_id' => $lessonId],
                ['quiz_score' => $isCorrect ? 100 : 0, 'last_practiced_at' => now()],
            );
        }

        $totalCount = count($answerKey);

        session()->put('quiz_result', [
            'correct' => $correctCount,
            'total' => $totalCount,
            'percentage' => (int) round($correctCount / $totalCount * 100),
        ]);

        return redirect()->route('quiz.results');
    }
}
