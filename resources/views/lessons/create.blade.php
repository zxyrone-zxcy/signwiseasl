@extends('layouts.signwise')

@section('title', 'Add lesson')

@section('content')
    <section class="page-width form-page"><a class="back-link" href="{{ route('lessons.index') }}">&#8592; Lesson catalog</a><div class="form-heading"><p class="eyebrow"><span></span> Catalog management</p><h1>Add a lesson</h1><p class="section-intro">Create a clear, focused practice entry for the SignWise catalog.</p></div>
        <form class="lesson-form" method="POST" action="{{ route('lessons.store') }}">@csrf @include('lessons._form', ['lesson' => null])<div class="form-actions"><a class="button button-outline" href="{{ route('lessons.index') }}">Cancel</a><button class="button button-primary" type="submit">Save lesson</button></div></form>
    </section>
@endsection