<?php

namespace App\Services;

use App\Models\Recording;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class StorageService
{
    public function getMaxStorageSize(): int
    {
        return (int) config('app.max_storage_size', 1024) * 1024 * 1024; // Convert MB to bytes
    }

    public function getMaxRecordingSize(): int
    {
        return (int) config('app.max_recording_size', 100) * 1024 * 1024; // Convert MB to bytes
    }

    public function getMaxRecordingTime(): int
    {
        return (int) config('app.max_recording_time', 3600); // seconds
    }

    public function getWarningThreshold(): int
    {
        return (int) config('app.storage_warning_threshold', 80); // percentage
    }

    public function getCurrentStorageUsage(): int
    {
        return Recording::sum('file_size');
    }

    public function getStorageUsageByMeeting(int $meetingId): int
    {
        return Recording::where('meeting_id', $meetingId)->sum('file_size');
    }

    public function getStorageUsageByUser(int $userId): int
    {
        return Recording::where('user_id', $userId)->sum('file_size');
    }

    public function getStorageUsagePercentage(): float
    {
        $currentUsage = $this->getCurrentStorageUsage();
        $maxStorage = $this->getMaxStorageSize();

        if ($maxStorage === 0) {
            return 0;
        }

        return round(($currentUsage / $maxStorage) * 100, 2);
    }

    public function getAvailableStorage(): int
    {
        return max(0, $this->getMaxStorageSize() - $this->getCurrentStorageUsage());
    }

    public function canStoreRecording(int $estimatedSize): bool
    {
        $availableStorage = $this->getAvailableStorage();
        return $estimatedSize <= $availableStorage && $estimatedSize <= $this->getMaxRecordingSize();
    }

    public function isStorageWarningLevel(): bool
    {
        return $this->getStorageUsagePercentage() >= $this->getWarningThreshold();
    }

    public function isStorageFull(): bool
    {
        return $this->getAvailableStorage() <= 0;
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStorageStats(): array
    {
        $currentUsage = $this->getCurrentStorageUsage();
        $maxStorage = $this->getMaxStorageSize();
        $usagePercentage = $this->getStorageUsagePercentage();
        $totalRecordings = Recording::count();

        return [
            'current_usage' => $currentUsage,
            'current_usage_formatted' => $this->formatBytes($currentUsage),
            'max_storage' => $maxStorage,
            'max_storage_formatted' => $this->formatBytes($maxStorage),
            'available_storage' => $this->getAvailableStorage(),
            'available_storage_formatted' => $this->formatBytes($this->getAvailableStorage()),
            'usage_percentage' => $usagePercentage,
            'total_recordings' => $totalRecordings,
            'is_warning_level' => $this->isStorageWarningLevel(),
            'is_storage_full' => $this->isStorageFull(),
            'average_file_size' => $totalRecordings > 0 ? $currentUsage / $totalRecordings : 0,
            'average_file_size_formatted' => $totalRecordings > 0 ? $this->formatBytes($currentUsage / $totalRecordings) : '0 B',
        ];
    }

    public function getRecordingStats(): array
    {
        $recordings = Recording::select([
            DB::raw('MIN(created_at) as oldest_recording'),
            DB::raw('MAX(created_at) as newest_recording'),
            DB::raw('AVG(file_size) as average_size'),
            DB::raw('AVG(duration) as average_duration'),
            DB::raw('COUNT(*) as total_count')
        ])->first();

        return [
            'oldest_recording' => $recordings->oldest_recording,
            'newest_recording' => $recordings->newest_recording,
            'average_size' => $recordings->average_size ?? 0,
            'average_size_formatted' => $this->formatBytes($recordings->average_size ?? 0),
            'average_duration' => $recordings->average_duration ?? 0,
            'average_duration_formatted' => $this->formatDuration($recordings->average_duration ?? 0),
            'total_count' => $recordings->total_count ?? 0,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) return '00:00:00';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function cleanupOldRecordings(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $oldRecordings = Recording::where('created_at', '<', $cutoffDate)->get();

        $deletedCount = 0;
        foreach ($oldRecordings as $recording) {
            if (Storage::exists($recording->file_path)) {
                Storage::delete($recording->file_path);
            }
            $recording->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }
}