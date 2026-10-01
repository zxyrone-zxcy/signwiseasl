@extends('layouts.signwise')

@section('title', 'How it works')

@section('content')
    <section class="page-width inner-hero"><p class="eyebrow"><span></span> Your learning path</p><h1>Practice makes room<br>for connection.</h1><p class="hero-description">SignWise helps independent learners explore a focused set of American Sign Language signs with clear references, a camera preview, and progress they can revisit.</p><a class="button button-primary" href="{{ route('lessons.index') }}">View the lessons <span aria-hidden="true">&#8594;</span></a></section>
    <section class="rhythm-band"><div class="page-width section-block">
        <p class="eyebrow"><span></span> Three steps, at your pace</p><h2>A calm place to begin.</h2>
        <div class="rhythm-grid how-grid">
            <article class="rhythm-card"><span class="step-number step-blue">01</span><h3>Learn the shape</h3><p>Each lesson introduces one everyday sign with a short description and a written movement reference.</p></article>
            <article class="rhythm-card"><span class="step-number step-teal">02</span><h3>Practice in view</h3><p>Turn on your camera when you are ready. The video preview runs locally in your browser and is not uploaded.</p></article>
            <article class="rhythm-card"><span class="step-number step-gold">03</span><h3>Track your journey</h3><p>Save completed practice and quiz scores to your account, then return to the signs you want to strengthen.</p></article>
        </div>
        <p class="notice-line">This learning prototype provides a camera preview and self-guided practice log. It does not automatically classify hand gestures.</p>
    </div></section>
@endsection