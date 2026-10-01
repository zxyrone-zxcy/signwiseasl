@extends('layouts.signwise')

@section('title', 'Your progress')

@section('content')
    <section class="page-width dashboard-page">
        <div class="dashboard-heading"><div><p class="eyebrow"><span></span> Your learning space</p><h1>Good to see you,<br>{{ auth()->user()->name }}.</h1><p class="section-intro">Every small practice session adds up. Pick up right where you left off.</p></div><a class="button button-primary" href="{{ route('quiz') }}">Start a quick quiz <span aria-hidden="true">&#8594;</span></a></div>
        <div class="stat-grid">
            <article class="stat-card"><span class="stat-kicker">Lessons completed</span><strong>{{ $completedCount }}<small> / {{ $lessonCount }}</small></strong><span>Keep building your foundation</span></article>
            <article class="stat-card stat-card-teal"><span class="stat-kicker">Pathway progress</span><strong>{{ $completionRate }}<small>%</small></strong><span class="progress-track"><i style="width: {{ $completionRate }}%"></i></span></article>
            <article class="stat-card stat-card-gold"><span class="stat-kicker">Latest quiz average</span><strong>{{ $averageScore === null ? '—' : (int) round($averageScore) }}<small>{{ $averageScore === null ? '' : '%' }}</small></strong><span>{{ $averageScore === null ? 'Take a quiz to see your score' : 'Scores from your saved practice' }}</span></article>
        </div>
        <div class="dashboard-section-heading"><div><p class="eyebrow"><span></span> Pick up where you left off</p><h2>Your lesson path</h2></div><a class="text-link" href="{{ route('lessons.index') }}">Browse catalog <span aria-hidden="true">&#8594;</span></a></div>
        <div class="progress-list">
            @foreach ($lessons as $lesson)
                @php($lessonProgress = $progress->firstWhere('lesson_id', $lesson->id))
                <a class="progress-row" href="{{ route('lessons.show', $lesson) }}"><span class="progress-symbol">{{ str_pad((string) $lesson->id, 2, '0', STR_PAD_LEFT) }}</span><span class="progress-lesson"><strong>{{ $lesson->title }}</strong><small>{{ $lesson->category }} · {{ $lesson->duration_minutes }} min</small></span><span class="progress-result">{{ $lessonProgress?->is_completed ? 'Completed' : ($lessonProgress ? 'In practice' : 'Not started') }}</span><span class="progress-score">{{ $lessonProgress?->quiz_score !== null ? $lessonProgress->quiz_score.'%' : '—' }}</span><span class="row-arrow" aria-hidden="true">&#8594;</span></a>
            @endforeach
        </div>
        <div class="manage-lessons"><div><strong>Lesson catalog management</strong><p>Add, edit, or remove lesson records for this project.</p></div><a class="button button-outline" href="{{ route('lessons.create') }}">Add a lesson</a></div>
    </section>
@endsection
