<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // ログアウト後は「おすすめ」タブへ
        return redirect()->route('items.index', ['tab' => 'recommend']);
    }
}
