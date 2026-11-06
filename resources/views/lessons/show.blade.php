@extends('layouts.signwise')

@section('title', $lesson->title)

@section('content')
    <section class="page-width lesson-detail-page">
        <a class="back-link" href="{{ route('lessons.index') }}">&#8592; All lessons</a>
        <div class="lesson-detail-grid">
            <div class="lesson-copy"><p class="eyebrow"><span></span> {{ $lesson->category }} · {{ $lesson->difficulty }}</p><h1>{{ $lesson->title }}</h1><p class="hero-description">{{ $lesson->description }}</p>
                <div class="sign-demo" data-sign-demo data-title="{{ $lesson->title }}" data-templates-url="{{ asset('sign-templates.json') }}">
                    <div class="sign-demo-head"><div><span class="micro-label">Watch the sign</span><strong>Follow the motion</strong></div><span class="sign-demo-badge">Real recorded motion</span></div>
                    <div class="sign-demo-stage">
                        <canvas data-demo-canvas role="img" aria-label="Animated hand diagram showing how to sign {{ $lesson->title }}"></canvas>
                        <p class="sign-demo-empty" data-demo-empty hidden>No recorded motion for this sign yet. Use the written reference below.</p>
                    </div>
                    <p class="sign-demo-note">Shown mirrored, like your camera preview.</p>
                    <div class="sign-demo-controls">
                        <button class="sign-demo-btn" type="button" data-demo-toggle aria-pressed="false">Pause</button>
                        <input class="sign-demo-scrub" type="range" min="0" max="1000" value="0" data-demo-scrub aria-label="Step through the sign">
                        <button class="sign-demo-btn" type="button" data-demo-speed aria-pressed="false">Slow motion</button>
                    </div>
                    <p class="sign-demo-note">The dotted line shows how your hand travels.</p>
                </div>
                <div class="reference-block"><span class="micro-label">Sign reference</span><p>{{ $lesson->sign_reference }}</p></div>
                <div class="tip-block"><span class="tip-mark">i</span><p><strong>Practice tip</strong><br>{{ $lesson->practice_tip }}</p></div>
                @auth
                    <form method="POST" action="{{ route('lessons.progress.store', $lesson) }}">@csrf<button class="button button-primary" type="submit">{{ $progress?->is_completed ? 'Practice this sign again' : 'Mark practice complete' }} <span aria-hidden="true">&#8594;</span></button></form>
                    @can('update', $lesson)<div class="lesson-admin-actions"><a href="{{ route('lessons.edit', $lesson) }}">Edit lesson</a><form method="POST" action="{{ route('lessons.destroy', $lesson) }}" onsubmit="return confirm('Remove this lesson from the catalog?')">@csrf @method('DELETE')<button type="submit">Delete</button></form></div>@endcan
                @else<a class="button button-primary" href="{{ route('login') }}">Log in to save practice <span aria-hidden="true">&#8594;</span></a>@endauth
            </div>
            <div class="practice-panel" data-sign-lesson data-title="{{ $lesson->title }}" data-templates-url="{{ asset('sign-templates.json') }}" @auth data-accuracy-url="{{ route('lessons.accuracy.store', $lesson) }}" @endauth>
                <div class="practice-head"><div><span class="micro-label">Guided practice</span><strong>Take your time</strong></div><span class="practice-live"><i></i> Camera optional</span></div>
                <div class="practice-screen"><div class="practice-placeholder" data-camera-placeholder><span class="practice-hand" aria-hidden="true">{{ ['Hello' => 'Hi', 'Thank you' => 'Thanks', 'Please' => 'Please', 'Sorry' => 'Sorry', 'Yes' => 'Yes', 'No' => 'No', 'Help' => 'Help', 'Good' => 'Good', 'More' => 'More', 'Love' => 'Love'][$lesson->title] ?? 'Sign' }}</span><span>Place your hand in the frame</span></div><video class="camera-video" data-camera-video autoplay playsinline muted aria-label="Live camera preview"></video><span class="camera-corner">Practice view</span></div>
                <div class="practice-feedback"><p class="sign-status practice-status" data-status aria-live="polite"></p>
                <div class="accuracy-meter" data-accuracy hidden><div class="accuracy-meter-head"><span>Gesture accuracy</span><strong data-accuracy-value>0%</strong></div><div class="accuracy-meter-track" role="presentation"><i data-accuracy-bar></i></div></div></div>
                <div class="practice-controls"><button class="button button-outline" type="button" data-camera-start>Start practice</button><button class="button button-outline" type="button" data-camera-retry hidden>Try again</button><button class="camera-stop" type="button" data-camera-stop hidden>Turn off</button></div>
                <p class="camera-message" data-camera-message aria-live="polite">Your camera feed stays on this device. SignWise does not record video.</p>
            </div>
        </div>

        @php($isFinished = (bool) $progress?->is_completed)
        <nav class="lesson-nav" aria-label="Lesson navigation">
            @if ($previousLesson)
                <a class="lesson-nav-link is-prev" href="{{ route('lessons.show', $previousLesson) }}"><span class="lesson-nav-arrow" aria-hidden="true">&#8592;</span><span class="lesson-nav-text"><small>Previous lesson</small><strong>{{ $previousLesson->title }}</strong></span></a>
            @else
                <a class="lesson-nav-link is-prev" href="{{ route('lessons.index') }}"><span class="lesson-nav-arrow" aria-hidden="true">&#8592;</span><span class="lesson-nav-text"><small>Back to</small><strong>All lessons</strong></span></a>
            @endif
            @if ($nextLesson)
                <a @class(['lesson-nav-link', 'is-next', 'is-ready' => $isFinished]) href="{{ route('lessons.show', $nextLesson) }}" data-next-lesson><span class="lesson-nav-text"><small data-next-label data-ready-text="Nice work! Continue with" data-idle-text="Up next">{{ $isFinished ? 'Nice work! Continue with' : 'Up next' }}</small><strong>{{ $nextLesson->title }}</strong></span><span class="lesson-nav-arrow" aria-hidden="true">&#8594;</span></a>
            @else
                <a @class(['lesson-nav-link', 'is-next', 'is-ready' => $isFinished]) href="{{ auth()->check() ? route('dashboard') : route('lessons.index') }}" data-next-lesson><span class="lesson-nav-text"><small data-next-label data-ready-text="You reached the end of the path" data-idle-text="Last lesson">{{ $isFinished ? 'You reached the end of the path' : 'Last lesson' }}</small><strong>{{ auth()->check() ? 'View your progress' : 'Browse all lessons' }}</strong></span><span class="lesson-nav-arrow" aria-hidden="true">&#8594;</span></a>
            @endif
        </nav>
    </section>
@endsection