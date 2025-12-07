<?php
// app/Http/Controllers/ItemController.php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Condition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\StoreItemRequest; // ← ここが超重要！この行があること

class ItemController extends Controller
{
   public function index(Request $request)
{
    $tab = $request->get('tab', 'recommend');
    $q   = trim((string) $request->get('q', ''));

    if ($tab === 'mylist') {
        if (auth()->check()) {
            // ログイン時：いいね済みだけ
            $query = auth()->user()->likedItems()->latest();
        } else {
            // 未ログイン：空（0件）を返す
            $query = Item::query()->whereRaw('1 = 0');
        }
    } else {
        // おすすめ：ログイン時は自分の出品を除外
        $query = Item::query()->latest();
        if (auth()->check()) {
            $query->where('user_id', '!=', auth()->id());
        }
    }

    if ($q !== '') {
        $query->where('items.name', 'like', "%{$q}%");
    }

    $items = $query->paginate(12)->appends(['tab' => $tab, 'q' => $q]);

    return view('items.index', compact('items', 'tab', 'q'));
}

    public function create()
    {
        return view('items.create', [
            'categories' => Category::all(),
            'conditions' => Condition::all(),
        ]);
    }

    // ← 型を StoreItemRequest に（完全一致）。ここが違うと検証が発火しません。
    public function store(StoreItemRequest $request)
    {
        $validated = $request->validated();

        $path = $request->file('image')->store('items', 'public');

        $data = [
            'user_id'      => auth()->id(),
            'image_path'   => $path,
            'condition_id' => $validated['condition_id'],
            'name'         => $validated['name'],
            'description'  => $validated['description'],
            'price'        => $validated['price'],
            'status'       => 1,
            'brand_name'   => $validated['brand'] ?? null,
        ];

        if (Schema::hasColumn('items', 'category_id')) {
            $firstCategoryId = $validated['category_ids'][0] ?? null;
            if (!is_null($firstCategoryId)) $data['category_id'] = $firstCategoryId;
        }

        $item = Item::create($data);
        $item->categories()->sync($validated['category_ids']);

        return redirect()->route('mypage.index')
            ->with('success', '出品が完了しました。');
    }

    public function show(Item $item)
    {
        $item->load([
            'user.profile','condition','categories','comments.user.profile',
        ])->loadCount(['likedBy as likes_count','comments']);

        $liked = auth()->check() ? auth()->user()->hasLiked($item) : false;

        $similar = Item::query()
            ->where('id', '!=', $item->id)
            ->when(auth()->check(), fn($q)=>$q->where('user_id','!=',auth()->id()))
            ->whereHas('categories', fn($q)=>$q->whereIn('categories.id',$item->categories->pluck('id')))
            ->latest()->take(8)->get();

        $sellerItems = Item::query()
            ->where('user_id',$item->user_id)->where('id','!=',$item->id)
            ->latest()->take(8)->get();

        return view('items.show', compact('item','liked','similar','sellerItems'));
    }
}
