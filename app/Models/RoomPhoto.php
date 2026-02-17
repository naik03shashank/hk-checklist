<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'room_id',
        'path',
        'captured_at',
        'has_timestamp_overlay'
    ];
    protected $casts = ['captured_at' => 'datetime', 'has_timestamp_overlay' => 'bool'];

    public function getUrlAttribute()
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }
        
        $path = 'storage/' . $this->path;
        // In local environments with symlinks, we just return the asset path
        // and let the browser's onerror handler in the view deal with missing files
        // to avoid expensive disk checks on every model access.
        return asset($path);
    }
}
