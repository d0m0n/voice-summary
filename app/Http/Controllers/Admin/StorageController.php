<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recording;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;

class StorageController extends Controller
{
    public function __construct(
        private StorageService $storageService
    ) {}

    public function index()
    {
        return view('admin.storage.index');
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'storage_stats' => $this->storageService->getStorageStats(),
            'recording_stats' => $this->storageService->getRecordingStats()
        ]);
    }

    public function recordings(): JsonResponse
    {
        $recordings = Recording::with(['user', 'meeting'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'recordings' => $recordings
        ]);
    }

    public function deleteRecording(Recording $recording): JsonResponse
    {
        try {
            $recording->delete();

            return response()->json([
                'success' => true,
                'message' => '録音が削除されました。',
                'storage_stats' => $this->storageService->getStorageStats()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '録音の削除に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cleanup(): JsonResponse
    {
        try {
            $deletedCount = $this->storageService->cleanupOldRecordings(30);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount}個の古い録音を削除しました。",
                'storage_stats' => $this->storageService->getStorageStats()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'クリーンアップに失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteSelectedRecordings(): JsonResponse
    {
        try {
            $request = request();
            $recordingIds = $request->input('recording_ids', []);

            if (empty($recordingIds)) {
                return response()->json([
                    'success' => false,
                    'message' => '削除する録音が選択されていません。'
                ], 400);
            }

            $deletedCount = Recording::whereIn('id', $recordingIds)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount}個の録音を削除しました。",
                'storage_stats' => $this->storageService->getStorageStats()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '選択された録音の削除に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
