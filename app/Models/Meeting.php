<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'status',
        'language',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(Summary::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    public function latestSummaries()
    {
        return $this->summaries()->orderBy('created_at', 'desc');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
