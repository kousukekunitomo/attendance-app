<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Item;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function items()
    {
        // ★ withTimestamps() を付けない
       return $this->belongsToMany(Item::class, 'category_item', 'category_id', 'item_id');
    }
}
