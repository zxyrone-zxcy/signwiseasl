@extends('layouts.signwise')

@section('title', 'Learn at your pace')

@section('content')
    <section class="hero-band">
        <div class="hero page-width">
            <div class="hero-copy">
                <p class="eyebrow"><span></span> Learning that meets you where you are</p>
                <h1>Learn sign language<br>with confidence.</h1>
                <p class="hero-description">Build practical signing skills at your own pace. Practice hand gestures with a friendly camera-based learning experience designed to support every step.</p>
                <div class="hero-actions"><a class="button button-primary button-arrow" href="{{ route('lessons.index') }}" aria-label="Explore lessons">&#8594;</a><a class="button button-outline" href="{{ route('lessons.index') }}">Explore lessons</a></div>
                <p class="privacy-note"><span aria-hidden="true">&#9673;</span> Camera access stays in your browser.</p>
            </div>
            <div class="preview-shell" data-camera-panel>
                <div class="preview-screen">
                    <img class="preview-photo" src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1100&q=85" alt="Learners practicing together in a welcoming classroom">
                    <video class="camera-video" data-camera-video autoplay playsinline muted aria-label="Live camera preview"></video>
                    <span class="preview-label">Practice preview</span><span class="camera-indicator" aria-hidden="true"></span>
                    <div class="recognition-panel"><div><span class="micro-label">Your next step</span><strong>Start with hello</strong></div><button class="camera-start" type="button" data-camera-start>Try your camera</button></div>
                </div>
                <p class="camera-message" data-camera-message aria-live="polite">Camera is off. You can browse lessons without turning it on.</p>
            </div>
        </div>
    </section>
    <section class="rhythm-band"><div class="page-width section-block">
        <p class="eyebrow"><span></span> A simple learning rhythm</p><h2>Small steps. Meaningful connection.</h2>
        <p class="section-intro">Short, supportive lessons help you build confidence through practice, not pressure.</p>
        <div class="rhythm-grid">
            <article class="rhythm-card"><span class="step-number step-blue">01</span><h3>Choose a lesson</h3><p>Start with useful signs for everyday moments, from a first hello to counting with ease.</p></article>
            <article class="rhythm-card"><span class="step-number step-teal">02</span><h3>Practise with your camera</h3><p>Follow the guided example and use a camera-based preview while you refine your movement.</p></article>
            <article class="rhythm-card"><span class="step-number step-gold">03</span><h3>Keep your progress</h3><p>Save practice attempts, revisit lessons, and celebrate every sign you learn along the way.</p></article>
        </div>
    </div></section>
    <section class="page-width section-block featured-block">
        <p class="eyebrow"><span></span> Start with the essentials</p><h2>Featured beginner lessons</h2>
        <p class="section-intro">Choose a topic that feels useful today and build a foundation you can return to anytime.</p>
        <div class="lesson-grid featured-grid">
            @foreach ($featuredLessons as $index => $lesson)
                <a class="lesson-card" href="{{ route('lessons.show', $lesson) }}"><div class="lesson-art art-{{ $index + 1 }}" aria-hidden="true"><span>{{ ['Hi', 'Thanks', 'Please'][$index] }}</span><b>{{ ['01', '02', '03'][$index] }}</b></div><div class="lesson-card-copy"><p class="micro-label">Lesson {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} · {{ $lesson->duration_minutes }} min</p><h3>{{ $lesson->title }}</h3><p>{{ $lesson->description }}</p></div></a>
            @endforeach
        </div>
        <a class="text-link" href="{{ route('lessons.index') }}">Browse all lessons <span aria-hidden="true">&#8594;</span></a>
    </section>
    <section class="page-width momentum-wrap"><div class="momentum-banner">
        <div><p class="eyebrow eyebrow-light"><span></span> Keep your momentum</p><h2>Every sign is progress.</h2><p>Review your learning path, revisit any lesson, and keep celebrating the moments that make communication feel more natural.</p></div>
        <a class="mini-progress" href="{{ auth()->check() ? route('dashboard') : route('register') }}"><div><span>Your first pathway</span><strong>Start today</strong></div><span class="progress-track"><i style="width: 38%"></i></span><span>10 beginner signs to explore</span></a>
    </div></section>
@endsection