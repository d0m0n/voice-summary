<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Recording;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecordingController extends Controller
{
    public function __construct(
        private StorageService $storageService
    ) {}

    public function store(Request $request, Meeting $meeting): JsonResponse
    {
        $request->validate([
            'audio_data' => 'required|file|mimes:webm,mp3,wav,ogg,mp4|max:' . ($this->storageService->getMaxRecordingSize() / 1024),
            'title' => 'nullable|string|max:255',
            'duration' => 'nullable|integer|min:0',
        ]);

        try {
            $file = $request->file('audio_data');
            $fileSize = $file->getSize();

            // Check storage capacity
            if (!$this->storageService->canStoreRecording($fileSize)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ストレージ容量が不足しています。',
                    'storage_stats' => $this->storageService->getStorageStats()
                ], 413);
            }

            // Generate unique filename
            $fileName = 'recording_' . $meeting->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

            // Store file
            $storedPath = $file->storeAs('recordings', $fileName, 'local');

            // Create recording record
            $recording = Recording::create([
                'meeting_id' => $meeting->id,
                'user_id' => auth()->id(),
                'title' => $request->title ?: '録音 - ' . now()->format('Y-m-d H:i:s'),
                'file_path' => $storedPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'duration' => $request->duration,
                'mime_type' => $this->getNormalizedMimeType($file->getMimeType(), $file->getClientOriginalExtension()),
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toISOString(),
                ],
                'recorded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'recording' => $recording->load('user'),
                'message' => '録音が保存されました。',
                'storage_stats' => $this->storageService->getStorageStats()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '録音の保存に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Meeting $meeting): JsonResponse
    {
        $recordings = $meeting->recordings()
            ->with('user')
            ->orderBy('recorded_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'recordings' => $recordings,
            'storage_stats' => $this->storageService->getStorageStats()
        ]);
    }

    public function stream(Recording $recording): Response
    {
        if (!Storage::exists($recording->file_path)) {
            abort(404, 'Recording file not found');
        }

        $filePath = Storage::path($recording->file_path);
        $fileSize = filesize($filePath);
        $mimeType = $recording->mime_type;

        // Ensure audio MIME type for WebM files
        if (str_ends_with($recording->file_name, '.webm') && !str_starts_with($mimeType, 'audio/')) {
            $mimeType = 'audio/webm';
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ];

        // Handle range requests for audio streaming
        $request = request();
        if ($request->hasHeader('Range')) {
            $range = $request->header('Range');
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = intval($matches[1]);
                $end = $matches[2] ? intval($matches[2]) : $fileSize - 1;
                $length = $end - $start + 1;

                $headers['Content-Range'] = "bytes $start-$end/$fileSize";
                $headers['Content-Length'] = $length;

                $handle = fopen($filePath, 'rb');
                fseek($handle, $start);
                $content = fread($handle, $length);
                fclose($handle);

                return response($content, 206, $headers);
            }
        }

        return response()->file($filePath, $headers);
    }

    public function destroy(Recording $recording): JsonResponse
    {
        // Check if user can delete this recording
        if (!auth()->user()->isAdmin() && $recording->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'この録音を削除する権限がありません。'
            ], 403);
        }

        try {
            // Delete file from storage
            if (Storage::exists($recording->file_path)) {
                Storage::delete($recording->file_path);
            }

            // Delete database record
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

    public function download(Recording $recording)
    {
        try {
            // Check if file exists
            if (!Storage::disk('local')->exists($recording->file_path)) {
                abort(404, '録音ファイルが見つかりません。');
            }

            $filePath = Storage::disk('local')->path($recording->file_path);

            // Generate download filename
            $downloadName = sprintf(
                '%s_%s_%s.%s',
                $recording->meeting->name ?? 'recording',
                $recording->user->name ?? 'unknown',
                $recording->created_at->format('Y-m-d_H-i-s'),
                pathinfo($recording->file_name, PATHINFO_EXTENSION)
            );

            // Clean filename for download
            $downloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $downloadName);

            return response()->download($filePath, $downloadName, [
                'Content-Type' => $recording->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"'
            ]);

        } catch (\Exception $e) {
            abort(500, '録音ファイルのダウンロードに失敗しました: ' . $e->getMessage());
        }
    }

    public function storageStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'storage_stats' => $this->storageService->getStorageStats(),
            'recording_stats' => $this->storageService->getRecordingStats()
        ]);
    }

    public function checkCapacity(Request $request): JsonResponse
    {
        $estimatedSize = $request->input('estimated_size', 0);

        return response()->json([
            'success' => true,
            'can_store' => $this->storageService->canStoreRecording($estimatedSize),
            'storage_stats' => $this->storageService->getStorageStats(),
            'max_recording_size' => $this->storageService->getMaxRecordingSize(),
            'max_recording_time' => $this->storageService->getMaxRecordingTime(),
        ]);
    }

    private function getNormalizedMimeType(string $detectedMimeType, string $extension): string
    {
        // Normalize MIME types based on file extension
        $extension = strtolower($extension);

        switch ($extension) {
            case 'mp4':
            case 'm4a':
                return 'audio/mp4';
            case 'webm':
                return 'audio/webm';
            case 'ogg':
                return 'audio/ogg';
            case 'wav':
                return 'audio/wav';
            case 'mp3':
                return 'audio/mpeg';
            default:
                // If detected MIME type starts with 'audio/', use it
                if (str_starts_with($detectedMimeType, 'audio/')) {
                    return $detectedMimeType;
                }
                // Default fallback
                return 'audio/webm';
        }
    }
}
