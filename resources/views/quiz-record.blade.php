@extends('layouts.signwise')
@section('title', 'Record sign templates')
@section('content')
<section class="page-width quiz-page sign-recorder" data-sign-recorder data-templates-url="{{ asset('sign-templates.json') }}">
    <a class="back-link" href="{{ route('quiz') }}">&#8592; Back to quiz</a>
    <div class="form-heading">
        <p class="eyebrow"><span></span> Developer tool</p>
        <h1>Record sign templates</h1>
        <p class="section-intro">Pick a sign, press Record, and perform it steadily for about two seconds. Record 3 to 5 takes per sign, with small natural differences. When you are done, download the file and put it in the <code>public</code> folder as <code>sign-templates.json</code>.</p>
    </div>

    <div class="recorder-grid">
        <div>
            <video class="sign-video" data-video autoplay playsinline muted aria-label="Your camera"></video>
            <div class="recorder-bar"><span data-bar></span></div>
            <p class="sign-status" data-status aria-live="polite">Camera is off.</p>
        </div>
        <div>
            <label for="lesson">Sign to record</label>
            <select id="lesson" data-lesson>
                @foreach ($lessons as $title)
                    <option value="{{ $title }}">{{ $title }}</option>
                @endforeach
            </select>
            <div class="sign-actions">
                <button class="button button-outline" type="button" data-start>Start camera</button>
                <button class="button button-primary" type="button" data-record disabled>Record</button>
                <button class="button button-outline" type="button" data-clear>Clear this sign</button>
                <button class="button button-outline" type="button" data-download>Download JSON</button>
            </div>
            <ul data-counts></ul>
        </div>
    </div>
</section>
@endsection
