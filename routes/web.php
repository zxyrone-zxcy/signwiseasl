<?php

use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Models\Lesson;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['featuredLessons' => Lesson::query()->orderBy('id')->limit(3)->get()]);
})->name('home');

Route::view('/how-it-works', 'how-it-works')->name('how-it-works');
Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ProgressController::class, 'index'])->name('dashboard');
    Route::get('/quiz', [ProgressController::class, 'quiz'])->name('quiz');
    Route::post('/quiz', [ProgressController::class, 'submitQuiz'])->name('quiz.submit');
    Route::post('/lessons/{lesson}/progress', [ProgressController::class, 'complete'])->name('lessons.progress.store');
    Route::resource('lessons', LessonController::class)->except(['index', 'show']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');

require __DIR__.'/auth.php';
