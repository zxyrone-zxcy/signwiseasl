@extends('layouts.signwise')

@section('title', 'Your progress')

@section('content')
    <section class="page-width dashboard-page">
        <div class="dashboard-heading"><div><p class="eyebrow"><span></span> Your learning space</p><h1>Good to see you,<br>{{ auth()->user()->name }}.</h1><p class="section-intro">Every small practice session adds up. Pick up right where you left off.</p></div><a class="button button-primary" href="{{ route('quiz') }}">Start a quick quiz <span aria-hidden="true">&#8594;</span></a></div>
        <div class="stat-grid">
            <article class="stat-card"><span class="stat-kicker">Lessons completed</span><strong>{{ $completedCount }}<small> / {{ $lessonCount }}</small></strong><span>Keep building your foundation</span></article>
            <article class="stat-card stat-card-teal"><span class="stat-kicker">Pathway progress</span><strong>{{ $completionRate }}<small>%</small></strong><span class="progress-track"><i style="width: {{ $completionRate }}%"></i></span></article>
            <article class="stat-card stat-card-rose"><span class="stat-kicker">Gesture accuracy</span><strong>{{ $averageAccuracy === null ? '—' : (int) round($averageAccuracy) }}<small>{{ $averageAccuracy === null ? '' : '%' }}</small></strong><span>{{ $averageAccuracy === null ? 'Practice with your camera to see it' : 'Average of your best attempts' }}</span></article>
            <article class="stat-card stat-card-gold"><span class="stat-kicker">Latest quiz average</span><strong>{{ $averageScore === null ? '—' : (int) round($averageScore) }}<small>{{ $averageScore === null ? '' : '%' }}</small></strong><span>{{ $averageScore === null ? 'Take a quiz to see your score' : 'Scores from your saved practice' }}</span></article>
        </div>

        <div class="daily-panel">
            <div class="daily-streak"><span class="daily-flame" aria-hidden="true">&#128293;</span><div><strong>{{ $streak }}<small> {{ $streak === 1 ? 'day' : 'days' }}</small></strong><span>{{ $streak === 0 ? 'Practice today to start a streak' : 'Practice streak' }}</span></div></div>
            <div class="daily-today"><span class="stat-kicker">Today</span>
                @php($todayTotal = $today ? $today->lessons_practiced + $today->quizzes_taken : 0)
                @if ($todayTotal > 0)
                    <p><strong>{{ $today->lessons_practiced }}</strong> {{ $today->lessons_practiced === 1 ? 'lesson' : 'lessons' }} practiced
                        @if ($today->quizzes_taken > 0)
                            &middot; <strong>{{ $today->quiz_correct }}/{{ $today->quiz_total }}</strong> quiz signs correct
                        @endif
                    </p>
                @else
                    <p>No practice yet today. A few minutes counts.</p>
                @endif
            </div>
            <ol class="daily-week" aria-label="Activity over the last 7 days">
                @foreach ($week as $day)
                    <li @class(['is-active' => $day['count'] > 0, 'is-today' => $day['isToday']])><span class="daily-dot" title="{{ $day['count'] }} {{ $day['count'] === 1 ? 'activity' : 'activities' }}">@if ($day['count'] > 0)&#10003;@endif</span><span class="daily-day">{{ $day['label'] }}</span></li>
                @endforeach
            </ol>
        </div>

        <div class="dashboard-section-heading"><div><p class="eyebrow"><span></span> Pick up where you left off</p><h2>Your lesson path</h2></div><a class="text-link" href="{{ route('lessons.index') }}">Browse catalog <span aria-hidden="true">&#8594;</span></a></div>
        <div class="progress-list">
            @foreach ($lessons as $lesson)
                @php($lessonProgress = $progress->firstWhere('lesson_id', $lesson->id))
                <a class="progress-row" href="{{ route('lessons.show', $lesson) }}"><span class="progress-symbol">{{ str_pad((string) $lesson->id, 2, '0', STR_PAD_LEFT) }}</span><span class="progress-lesson"><strong>{{ $lesson->title }}</strong><small>{{ $lesson->category }} · {{ $lesson->duration_minutes }} min</small></span><span class="progress-result">{{ $lessonProgress?->is_completed ? 'Completed' : ($lessonProgress ? 'In practice' : 'Not started') }}</span><span class="progress-accuracy">@if ($lessonProgress?->best_accuracy !== null)<span @class(['acc-pill', 'is-great' => $lessonProgress->best_accuracy >= 80, 'is-good' => $lessonProgress->best_accuracy >= 60 && $lessonProgress->best_accuracy < 80, 'is-low' => $lessonProgress->best_accuracy < 60]) title="Best gesture accuracy">{{ $lessonProgress->best_accuracy }}%</span>@else<span class="acc-empty">—</span>@endif</span><span class="progress-score">{{ $lessonProgress?->quiz_score !== null ? $lessonProgress->quiz_score.'%' : '—' }}</span><span class="row-arrow" aria-hidden="true">&#8594;</span></a>
            @endforeach
        </div>
        @if (Auth::user()->is_admin == 1)
        <div class="manage-lessons"><div><strong>Lesson catalog management</strong><p>Add, edit, or remove lesson records for this project.</p></div><a class="button button-outline" href="{{ route('lessons.create') }}">Add a lesson</a></div>
        @endif
    </section>
@endsection
