<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Features;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // セッションに初回フラグ
        $request->session()->put('after_register', true);

        // DBにも初回プロフ設定フラグを立てる（セッション切れ対策）
        if ($request->user()) {
            $request->user()->forceFill(['needs_profile_setup' => true])->save();
        }

        // メール認証を案内（機能ONのため）
        if (Features::enabled(Features::emailVerification())) {
            return redirect()->route('verification.notice');
        }

        // （メール認証をOFFにする場合はこちらに来る）
        return redirect()->route('profile.edit');
    }
}
