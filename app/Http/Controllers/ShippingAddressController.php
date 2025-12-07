<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShippingAddressController extends Controller
{
    public function edit(Request $request)
    {
        $profile = auth()->user()->profile; // null あり得る
        $itemId  = (int) $request->query('item'); // 戻り先用

        return view('address.edit', compact('profile', 'itemId'));
    }

    public function update(Request $request)
    {
        $v = $request->validate([
            'postal_code'   => ['required','regex:/^\d{3}-?\d{4}$/'],
            'address'       => ['required','string','max:255'],
            'building_name' => ['nullable','string','max:255'],
            'item_id'       => ['nullable','integer','exists:items,id'],
        ],[
            'postal_code.regex' => '郵便番号は 123-4567 または 1234567 の形式で入力してください。',
        ]);

        // 郵便番号を 123-4567 に整形
        $digits = preg_replace('/\D/','', $v['postal_code']);
        $postal = mb_strlen($digits) === 7 ? substr($digits,0,3).'-'.substr($digits,3) : $v['postal_code'];

        $user    = auth()->user();
        $profile = $user->profile()->firstOrCreate([], []); // なければ作成

        $profile->postal_code   = $postal;
        $profile->address       = $v['address'];
        $profile->building_name = $v['building_name'] ?? null;
        $profile->save();

        // 戻り先：購入画面（item_id があれば）/ なければマイページ等
        if (!empty($v['item_id'])) {
            return redirect()
                ->route('purchase.show', $v['item_id'])
                ->with('success', '配送先を更新しました。');
        }
        return redirect()->route('mypage.index')->with('success', '配送先を更新しました。');
    }
}
