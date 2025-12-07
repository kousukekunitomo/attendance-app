<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'condition_id',
        'name',
        'description',
        'price',
        'status',
        'brand_name',
        'category_id',
    ];

    /** 出品者 */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 状態 */
    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    /** カテゴリ（多対多） */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item', 'item_id', 'category_id');
    }

    /** いいねしたユーザー（多対多） */
    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'likes', 'item_id', 'user_id')
                    ->withTimestamps();
    }

    /** コメント（1対多） */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 画像URL（S3の絶対URLはそのまま／ローカルは /storage に正規化）
     * 例:
     *  - https://... => そのまま返す
     *  - public/items/xxx.jpg, storage/items/xxx.jpg, items/xxx.jpg => /storage/items/xxx.jpg に統一
     */
    public function getImageUrlAttribute(): string
    {
        $path = (string) ($this->image_path ?? '');

        if ($path === '') {
            return asset('images/placeholder.png');
        }

        // S3 などフルURLはそのまま
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // public/ や storage/ を吸収して /storage/... に統一
        $normalized = ltrim(preg_replace('#^(public|storage)/#', '', $path), '/');

        if ($normalized !== '' && Storage::disk('public')->exists($normalized)) {
            // 現在のホスト/ポートで動く相対URL
            return url('storage/' . $normalized);
        }

        // 保存されていない／見つからない場合
        return asset('images/placeholder.png');
    }
}
