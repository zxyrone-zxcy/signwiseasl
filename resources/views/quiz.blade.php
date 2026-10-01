@extends('layouts.signwise')

@section('title', 'Practice quiz')

@section('content')
    <section class="page-width quiz-page"><a class="back-link" href="{{ route('dashboard') }}">&#8592; Your progress</a><div class="form-heading"><p class="eyebrow"><span></span> Quick review · {{ $questions->count() }} questions</p><h1>Practice what<br>you remember.</h1><p class="section-intro">Read each sign reference, make the gesture, then choose its meaning. Your result is saved to your progress.</p></div>
        <form method="POST" action="{{ route('quiz.submit') }}" class="quiz-form">@csrf
            @foreach ($questions as $question)
                @php($lesson = $question['lesson'])
                <fieldset class="quiz-question"><legend><span class="step-number step-blue">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $lesson->sign_reference }}</span></legend><div class="quiz-options">@foreach ($question['choices'] as $choice)<label class="quiz-option"><input type="radio" name="answers[{{ $lesson->id }}]" value="{{ $choice->id }}" required><span>{{ $choice->title }}</span></label>@endforeach</div></fieldset>
            @endforeach
            <button class="button button-primary" type="submit">Submit quiz <span aria-hidden="true">&#8594;</span></button>
        </form>
    </section>
@endsection