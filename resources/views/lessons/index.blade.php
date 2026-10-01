@extends('layouts.signwise')

@section('title', 'Lesson catalog')

@section('content')
    <section class="page-width catalog-page">
        <div class="catalog-heading"><div><p class="eyebrow"><span></span> Start with the essentials</p><h1>A sign for every<br>first conversation.</h1><p class="section-intro">Explore a focused set of beginner ASL signs, each with a clear reference and room to practice.</p></div>@auth<a class="button button-outline" href="{{ route('lessons.create') }}">Add a lesson <span aria-hidden="true">+</span></a>@endauth</div>
        <div class="catalog-filter" aria-label="Lesson catalog summary"><span>Beginner collection</span><span>{{ $lessons->total() }} lessons</span></div>
        <div class="lesson-grid catalog-grid">
            @foreach ($lessons as $lesson)
                <a class="lesson-card" href="{{ route('lessons.show', $lesson) }}"><div class="lesson-art art-{{ (($lesson->id - 1) % 3) + 1 }}" aria-hidden="true"><span>{{ $lesson->title }}</span><b>{{ str_pad((string) $lesson->id, 2, '0', STR_PAD_LEFT) }}</b></div><div class="lesson-card-copy"><p class="micro-label">{{ $lesson->category }} · {{ $lesson->duration_minutes }} min</p><h2>{{ $lesson->title }}</h2><p>{{ $lesson->description }}</p><span class="card-link">Open lesson <span aria-hidden="true">&#8594;</span></span></div></a>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $lessons->links() }}</div>
    </section>
@endsection