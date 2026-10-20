@extends('layouts.signwise')
@section('title', 'Practice quiz')
@section('content')
<section class="page-width quiz-page" data-sign-quiz data-templates-url="{{ asset('sign-templates.json') }}" data-debug="{{ request()->boolean('debug') ? '1' : '0' }}">
    <a class="back-link" href="{{ route('dashboard') }}">&#8592; Your progress</a>
    <div class="form-heading">
        <p class="eyebrow"><span></span> Quick review{{ $questions->isNotEmpty() ? ' · '.$questions->count().' questions' : '' }}</p>
        <h1>Show what<br>you remember.</h1>
        <p class="section-intro">Flip each card, then sign the word in front of your camera. You get up to 3 retries per question. Video stays in your browser and is never recorded.</p>
    </div>

    @if ($questions->isEmpty())
        <div class="quiz-empty">
            <p>No sign templates have been recorded yet, so the camera quiz has nothing to check against.</p>
            <div class="form-actions">
                <a class="button button-primary" href="{{ route('dashboard') }}">Go to your dashboard</a>
                @if (app()->environment('local'))
                    <a class="button button-outline" href="{{ route('quiz.record') }}">Record sign templates</a>
                @endif
            </div>
        </div>
    @else

    <form method="POST" action="{{ route('quiz.submit') }}" class="quiz-form" data-quiz-form>
        @csrf
        @foreach ($questions as $question)
            @php($lesson = $question['lesson'])
            <div class="sign-card" data-card data-title="{{ $lesson->title }}" @if (! $loop->first) hidden @endif>
                <div class="sign-card-inner">
                    <div class="sign-card-face sign-card-front">
                        <p class="sign-card-count">Question {{ $loop->iteration }} of {{ $questions->count() }}</p>
                        <h2 class="sign-card-word">{{ $lesson->title }}</h2>
                        <details class="sign-hint">
                            <summary>Show hint</summary>
                            <p>{{ $lesson->sign_reference }}</p>
                        </details>
                        <button class="button button-primary" type="button" data-flip>Flip &amp; start camera</button>
                    </div>
                    <div class="sign-card-face sign-card-back">
                        <video class="sign-video" data-video autoplay playsinline muted aria-label="Your camera"></video>
                        <p class="sign-status" data-status aria-live="polite"></p>
                        <div class="sign-actions">
                            <button class="button button-outline" type="button" data-retry hidden>Try again</button>
                            <button class="button button-primary" type="button" data-next hidden>Next question</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="results[{{ $lesson->id }}]" value="0" data-result>
            </div>
        @endforeach
        <noscript><p>This quiz needs JavaScript and a camera.</p></noscript>
    </form>
    @endif
</section>
@endsection
