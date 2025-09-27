<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Recording extends Model
{
    protected $fillable = [
        'meeting_id',
        'user_id',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'duration',
        'mime_type',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'file_size_formatted',
        'duration_formatted',
        'stream_url'
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return '00:00:00';

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getStreamUrlAttribute(): string
    {
        return route('recordings.stream', $this->id);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($recording) {
            if (Storage::exists($recording->file_path)) {
                Storage::delete($recording->file_path);
            }
        });
    }
}
