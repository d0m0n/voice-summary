<?php

use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// 認証が必要ないページ
Route::get('/', function () {
    return redirect('/meetings');
});

// さくらサーバー用デバッグルート
Route::get('/debug-route', function () {
    return response()->json([
        'message' => 'Laravel routing is working',
        'request_uri' => request()->getRequestUri(),
        'path_info' => request()->getPathInfo(),
        'query_string' => request()->getQueryString(),
    ]);
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
        // 会議管理
        Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
        Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

        // ユーザー管理・ストレージ管理
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', UserManagementController::class)->except(['show']);

            // ストレージ管理
            Route::get('storage', [StorageController::class, 'index'])->name('storage.index');
            Route::get('storage/stats', [StorageController::class, 'stats'])->name('storage.stats');
            Route::get('storage/recordings', [StorageController::class, 'recordings'])->name('storage.recordings');
            Route::delete('storage/recordings/{recording}', [StorageController::class, 'deleteRecording'])->name('storage.recordings.destroy');
            Route::post('storage/cleanup', [StorageController::class, 'cleanup'])->name('storage.cleanup');
            Route::post('storage/recordings/delete-selected', [StorageController::class, 'deleteSelectedRecordings'])->name('storage.recordings.delete-selected');
        });
    });

    // 会議詳細（全ユーザーアクセス可能、パラメータルートは最後に）
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');

    // 要約関連のAPI（全員要約閲覧可能、利用者以上が要約生成可能）
    Route::get('/meetings/{meeting}/summaries', [SummaryController::class, 'getSummaries'])->name('summaries.index');

    // 録音関連のAPI（全員録音閲覧可能）
    Route::get('/meetings/{meeting}/recordings', [RecordingController::class, 'index'])->name('recordings.index');
    Route::get('/recordings/{recording}/stream', [RecordingController::class, 'stream'])->name('recordings.stream');
    Route::get('/recordings/{recording}/download', [RecordingController::class, 'download'])->name('recordings.download');
    Route::get('/storage/stats', [RecordingController::class, 'storageStats'])->name('storage.stats');

    // 利用者以上（管理者・利用者）が要約・録音生成可能
    Route::middleware('moderator')->group(function () {
        Route::post('/meetings/{meeting}/summaries', [SummaryController::class, 'generateSummary'])->name('summaries.store');
        Route::post('/meetings/{meeting}/recordings', [RecordingController::class, 'store'])->name('recordings.store');
        Route::post('/recordings/check-capacity', [RecordingController::class, 'checkCapacity'])->name('recordings.checkCapacity');
    });

    // 管理者のみが要約・録音削除可能、ただし録音は自分のもののみ削除可
    Route::middleware('admin')->group(function () {
        Route::delete('/summaries/{summary}', [SummaryController::class, 'deleteSummary'])->name('summaries.destroy');
    });

    // 録音削除（管理者は全て、一般ユーザーは自分のもののみ）
    Route::delete('/recordings/{recording}', [RecordingController::class, 'destroy'])->name('recordings.destroy');

    // プロフィール管理
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
