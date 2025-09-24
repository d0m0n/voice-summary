<?php

use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

// 認証が必要ないページ
Route::get('/', function () {
    return redirect('/meetings');
});

// 認証が必要なページ
Route::middleware(['auth', 'verified'])->group(function () {
    // ダッシュボード（会議一覧にリダイレクト）
    Route::get('/dashboard', function () {
        return redirect('/meetings');
    })->name('dashboard');

    // 会議一覧（全ユーザーアクセス可能）
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');

    // 会議の管理者限定操作（具体的なルートを先に定義）
    Route::middleware('admin')->group(function () {
        Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
        Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    });

    // 会議詳細（全ユーザーアクセス可能、パラメータルートは最後に）
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');

    // 要約関連のAPI（管理者のみ要約生成、全員要約閲覧可能）
    Route::get('/meetings/{meeting}/summaries', [SummaryController::class, 'getSummaries'])->name('summaries.index');
    
    Route::middleware('admin')->group(function () {
        Route::post('/meetings/{meeting}/summaries', [SummaryController::class, 'generateSummary'])->name('summaries.store');
        Route::delete('/summaries/{summary}', [SummaryController::class, 'deleteSummary'])->name('summaries.destroy');
    });

    // プロフィール管理
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
