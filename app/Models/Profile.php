<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_image',
        'postal_code',
        'address',
        'building_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** プロフィール画像URL（Item と同じ戦略） */
    public function getImageUrlAttribute(): string
    {
        $path = (string) ($this->profile_image ?? '');

        if ($path === '') {
            return asset('images/placeholder.png');
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $normalized = ltrim(preg_replace('#^(public|storage)/#', '', $path), '/');

        if ($normalized !== '' && Storage::disk('public')->exists($normalized)) {
            return url('storage/' . $normalized);
        }

        return asset('images/placeholder.png');
    }
}
