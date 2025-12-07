<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'price',
        'payment_method',
        'quantity',
        'shipping_fee',
        'total',
        'stripe_payment_id',

        // ▼ 注文ごとの配送先（今回の主役）
        'delivery_postal_code',
        'delivery_address',
        'delivery_building_name',

        // ▼ 互換（テーブルに残っている場合のみ使われます）
        'postal_code',
        'address',
        'building_name',
        'delivery_building', // 誤名が残っている場合の互換
    ];

    protected $casts = [
        'user_id'      => 'int',
        'item_id'      => 'int',
        'price'        => 'int',
        'quantity'     => 'int',
        'shipping_fee' => 'int',
        'total'        => 'int',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Item::class);
    }
}
