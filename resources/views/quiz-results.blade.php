@extends('layouts.signwise')

@section('title', 'Quiz results')

@section('content')
    <section class="page-width quiz-page quiz-results-page">
        <a class="back-link" href="{{ route('dashboard') }}">&#8592; Your progress</a>
        <div class="form-heading">
            <p class="eyebrow"><span></span> Quiz complete</p>
            <h1>Your score.</h1>
            <p class="section-intro">Your answers have been saved to your progress.</p>
        </div>
        <div class="quiz-result-panel" role="status" aria-live="polite">
            <p class="quiz-result-score">{{ $result['percentage'] }}%</p>
            <p class="quiz-result-count">{{ $result['correct'] }} of {{ $result['total'] }} correct</p>
        </div>
        <div class="quiz-result-actions">
            <a class="button button-primary" href="{{ route('quiz') }}">Try another quiz <span aria-hidden="true">&#8594;</span></a>
        </div>
    </section>
@endsection
