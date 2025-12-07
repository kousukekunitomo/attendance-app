<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Item;

class AddressController extends Controller
{
    /** /items/{item}/address/edit ・・・初期値はプロフィールのみを使用 */
    public function edit(Request $request, Item $item): View
    {
        $user    = auth()->user()->load('profile');
        $profile = $user->profile; // プロフィール行が無い場合は null

        return view('address.edit', [
            'item'    => $item,
            'profile' => $profile, // 初期値：プロフィール（セッション値は無視）
        ]);
    }

    /** /items/{item}/address ・・・プロフィールは更新せずセッションに保存→購入画面へ */
    public function update(UpdateAddressRequest $request, Item $item): RedirectResponse
    {
        $data = $request->validated();
        $data['delivery_postal_code'] = $this->normalizePostal($data['delivery_postal_code'] ?? '');

        // 一時配送先としてセッション保存（プロフィールは触らない）
        session()->put('delivery', [
            'delivery_postal_code'   => $data['delivery_postal_code'],
            'delivery_address'       => $data['delivery_address'],
            'delivery_building_name' => $data['delivery_building_name'] ?? '',
        ]);

        // どの商品で更新したかも保存（購入画面での出し分けに使用）
        session()->put('delivery_item_id', $item->id);

        return redirect()
            ->route('purchase.show', ['item' => $item->id])
            ->with('status', '配送先を更新しました。');
    }

    /** 郵便番号を 123-4567 に整形 */
    private function normalizePostal(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        return strlen($digits) === 7
            ? substr($digits, 0, 3) . '-' . substr($digits, 3)
            : $value;
    }
}
