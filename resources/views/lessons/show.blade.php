@extends('layouts.signwise')

@section('title', $lesson->title)

@section('content')
    <section class="page-width lesson-detail-page">
        <a class="back-link" href="{{ route('lessons.index') }}">&#8592; All lessons</a>
        <div class="lesson-detail-grid">
            <div class="lesson-copy"><p class="eyebrow"><span></span> {{ $lesson->category }} · {{ $lesson->difficulty }}</p><h1>{{ $lesson->title }}</h1><p class="hero-description">{{ $lesson->description }}</p>
                <div class="reference-block"><span class="micro-label">Sign reference</span><p>{{ $lesson->sign_reference }}</p></div>
                <div class="tip-block"><span class="tip-mark">i</span><p><strong>Practice tip</strong><br>{{ $lesson->practice_tip }}</p></div>
                @auth
                    <form method="POST" action="{{ route('lessons.progress.store', $lesson) }}">@csrf<button class="button button-primary" type="submit">{{ $progress?->is_completed ? 'Practice this sign again' : 'Mark practice complete' }} <span aria-hidden="true">&#8594;</span></button></form>
                    <div class="lesson-admin-actions"><a href="{{ route('lessons.edit', $lesson) }}">Edit lesson</a><form method="POST" action="{{ route('lessons.destroy', $lesson) }}" onsubmit="return confirm('Remove this lesson from the catalog?')">@csrf @method('DELETE')<button type="submit">Delete</button></form></div>
                @else<a class="button button-primary" href="{{ route('login') }}">Log in to save practice <span aria-hidden="true">&#8594;</span></a>@endauth
            </div>
            <div class="practice-panel" data-camera-panel>
                <div class="practice-head"><div><span class="micro-label">Guided practice</span><strong>Take your time</strong></div><span class="practice-live"><i></i> Camera optional</span></div>
                <div class="practice-screen"><div class="practice-placeholder" data-camera-placeholder><span class="practice-hand" aria-hidden="true">{{ ['Hello' => 'Hi', 'Thank you' => 'Thanks', 'Please' => 'Please', 'Sorry' => 'Sorry', 'Yes' => 'Yes', 'No' => 'No', 'Help' => 'Help', 'Good' => 'Good', 'More' => 'More', 'Love' => 'Love'][$lesson->title] ?? 'Sign' }}</span><span>Place your hand in the frame</span></div><video class="camera-video" data-camera-video autoplay playsinline muted aria-label="Live camera preview"></video><span class="camera-corner">Practice view</span></div>
                <div class="practice-controls"><button class="button button-outline" type="button" data-camera-start>Turn on camera</button><button class="camera-stop" type="button" data-camera-stop hidden>Turn off</button></div>
                <p class="camera-message" data-camera-message aria-live="polite">Your camera feed stays on this device. SignWise does not record video.</p>
            </div>
        </div>
    </section>
@endsection