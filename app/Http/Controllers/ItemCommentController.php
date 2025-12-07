<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemCommentRequest;
use App\Models\Item;

class ItemCommentController extends Controller
{
    /** 商品へのコメント投稿 */
    public function store(StoreItemCommentRequest $request, Item $item)
    {
        // 念のための保険（ルートでauthを掛けていれば通常ここは通らない）
        if (!$request->user()) {
            return redirect()->route('login', [
                'intended' => route('items.show', $item),
            ]);
        }

        $item->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated()['content'],
        ]);

        return redirect()
            ->route('items.show', $item)
            ->with('status', 'コメントを投稿しました。')
            ->withFragment('comments'); // #comments へスクロール
    }
}
