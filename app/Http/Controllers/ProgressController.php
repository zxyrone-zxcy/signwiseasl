<?php

namespace App\Http\Controllers;

use App\Models\DailyActivity;
use App\Models\Lesson;
use App\Models\UserProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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

        $recentActivity = $user->dailyActivities()
            ->where('activity_date', '>=', today()->subDays(6))
            ->get()
            ->keyBy(fn (DailyActivity $activity): string => $activity->activity_date->toDateString());

        $week = collect(range(6, 0))->map(function (int $daysAgo) use ($recentActivity): array {
            $date = today()->subDays($daysAgo);
            $activity = $recentActivity->get($date->toDateString());

            return [
                'label' => $date->format('D'),
                'isToday' => $daysAgo === 0,
                'count' => $activity === null ? 0 : $activity->lessons_practiced + $activity->quizzes_taken,
            ];
        });

        return view('dashboard', [
            'lessons' => $lessons,
            'progress' => $progress,
            'completedCount' => $completedCount,
            'lessonCount' => $lessonCount,
            'completionRate' => $lessonCount === 0 ? 0 : (int) round($completedCount / $lessonCount * 100),
            'today' => $recentActivity->get(today()->toDateString()),
            'week' => $week,
            'streak' => DailyActivity::streakFrom(
                $user->dailyActivities()->orderByDesc('activity_date')->limit(400)->pluck('activity_date')
                    ->map(fn ($date): string => $date->toDateString()),
            ),
            'averageAccuracy' => $user->progress()->whereNotNull('best_accuracy')->avg('best_accuracy'),
            'averageScore' => $user->progress()->whereNotNull('quiz_score')->avg('quiz_score'),
        ]);
    }

    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        $request->user()->progress()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            ['is_completed' => true, 'last_practiced_at' => now()],
        );

        DailyActivity::recordFor($request->user(), ['lessons_practiced' => 1]);

        return redirect()->route('lessons.show', $lesson)->with('status', 'Practice attempt saved to your progress.');
    }

    public function recordAccuracy(Request $request, Lesson $lesson): JsonResponse
    {
        $validated = $request->validate([
            'accuracy' => ['required', 'integer', 'between:0,100'],
        ]);

        $progress = UserProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);
        $progress->recordAccuracy($validated['accuracy']);
        $progress->last_practiced_at = now();
        $progress->save();

        return response()->json([
            'best_accuracy' => $progress->best_accuracy,
            'last_accuracy' => $progress->last_accuracy,
        ]);
    }

    public function quiz(): View
    {
        session()->forget('quiz_result');

        $titles = $this->templatedLessonTitles();

        $lessons = Lesson::query()
            ->whereIn('title', $titles)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        if ($lessons->isEmpty()) {
            return view('quiz', ['questions' => collect()]);
        }

        // Camera quiz: each question is just a lesson. The learner signs it and the browser reports pass/fail.
        $questions = $lessons->map(
            fn (Lesson $lesson): array => ['lesson' => $lesson],
        );

        session(['quiz.answer_key' => $questions->mapWithKeys(
            fn (array $question): array => [$question['lesson']->id => $question['lesson']->id],
        )->all()]);

        return view('quiz', ['questions' => $questions]);
    }

    /**
     * Titles that have a recorded sign template in public/sign-templates.json.
     *
     * @return array<int, string>
     */
    private function templatedLessonTitles(): array
    {
        $path = public_path('sign-templates.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            array_keys($decoded),
            fn ($title): bool => is_string($title) && $title !== '',
        ));
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
        $validated = $request->validate([
            'results' => ['required', 'array'],
            'results.*' => ['required', 'boolean'],
            'accuracy' => ['nullable', 'array'],
            'accuracy.*' => ['nullable', 'integer', 'between:0,100'],
        ]);
        $accuracies = $validated['accuracy'] ?? [];
        $results = $validated['results'];

        $answerKey = session()->pull('quiz.answer_key');

        if (! is_array($answerKey) || count($answerKey) === 0) {
            throw ValidationException::withMessages(['results' => 'Start a new practice quiz before submitting.']);
        }

        $correctCount = 0;

        foreach ($answerKey as $lessonId => $unused) {
            $isCorrect = (bool) ($results[$lessonId] ?? false);
            $correctCount += (int) $isCorrect;

            $progress = UserProgress::query()->firstOrNew(
                ['user_id' => $request->user()->id, 'lesson_id' => $lessonId],
            );
            $progress->quiz_score = $isCorrect ? 100 : 0;
            $progress->last_practiced_at = now();

            if (isset($accuracies[$lessonId])) {
                $progress->recordAccuracy((int) $accuracies[$lessonId]);
            }

            $progress->save();
        }

        $totalCount = count($answerKey);

        DailyActivity::recordFor($request->user(), [
            'quizzes_taken' => 1,
            'quiz_correct' => $correctCount,
            'quiz_total' => $totalCount,
        ]);

        session()->put('quiz_result', [
            'correct' => $correctCount,
            'total' => $totalCount,
            'percentage' => (int) round($correctCount / $totalCount * 100),
        ]);

        return redirect()->route('quiz.results');
    }
}
