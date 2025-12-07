<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MyPageController extends Controller
{
    /**
     * マイページ
     * ?tab=listed | purchased（default: listed）
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tab  = $request->query('tab', 'listed'); // 出品がデフォルト

        if ($tab === 'purchased') {
            // purchases ピボットを使って購入日時順に表示
            // ※ purchases に created_at がある前提。無い場合は 'purchases.id' などに変更
            $items = $user->purchasedItems()
                ->with(['categories', 'condition', 'user.profile'])
                ->orderByDesc('purchases.created_at')
                ->paginate(12)
                ->withQueryString();
        } else {
            // 自分の出品
            $items = $user->items()
                ->with(['categories', 'condition'])
                ->latest()
                ->paginate(12)
                ->withQueryString();

            $tab = 'listed';
        }

        return view('mypage.index', compact('user', 'items', 'tab'));
    }
}
