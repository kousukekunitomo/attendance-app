<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // セッションに url.intended があればそこへ。
        // 無ければ マイリストタブ にフォールバック
        return redirect()->intended(route('items.index', ['tab' => 'mylist']));
    }
}
