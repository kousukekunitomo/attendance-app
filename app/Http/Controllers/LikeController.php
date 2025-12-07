<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, Item $item)
    {
        $user = $request->user();

        $liked = $user->likedItems()
            ->where('items.id', $item->id)
            ->exists();

        if ($liked) {
            $user->likedItems()->detach($item->id);
            $liked = false;
        } else {
            $user->likedItems()->attach($item->id);
            $liked = true;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'liked' => $liked,
                'count' => $item->likedBy()->count(),
            ]);
        }

        return back();
    }
}
