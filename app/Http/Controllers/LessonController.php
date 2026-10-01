<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('lessons.index', [
            'lessons' => Lesson::query()->orderBy('id')->paginate(9),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('lessons.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLessonRequest $request): RedirectResponse
    {
        $lesson = Lesson::create($request->validated());

        return redirect()->route('lessons.show', $lesson)->with('status', 'Lesson added to the catalog.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson): View
    {
        return view('lessons.show', [
            'lesson' => $lesson,
            'progress' => auth()->user()?->progress()->whereBelongsTo($lesson)->first(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lesson $lesson): View
    {
        return view('lessons.edit', ['lesson' => $lesson]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        return redirect()->route('lessons.show', $lesson)->with('status', 'Lesson changes saved.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return redirect()->route('lessons.index')->with('status', 'Lesson removed from the catalog.');
    }
}
