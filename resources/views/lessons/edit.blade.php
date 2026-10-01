@extends('layouts.signwise')

@section('title', 'Edit '.$lesson->title)

@section('content')
    <section class="page-width form-page"><a class="back-link" href="{{ route('lessons.show', $lesson) }}">&#8592; Back to lesson</a><div class="form-heading"><p class="eyebrow"><span></span> Catalog management</p><h1>Edit lesson</h1><p class="section-intro">Update the details learners see in the catalog.</p></div>
        <form class="lesson-form" method="POST" action="{{ route('lessons.update', $lesson) }}">@csrf @method('PUT') @include('lessons._form', ['lesson' => $lesson])<div class="form-actions"><a class="button button-outline" href="{{ route('lessons.show', $lesson) }}">Cancel</a><button class="button button-primary" type="submit">Save changes</button></div></form>
    </section>
@endsection