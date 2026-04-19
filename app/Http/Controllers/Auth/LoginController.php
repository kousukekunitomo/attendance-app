<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $remember    = $request->boolean('remember', false);

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // ✅ 管理者は intended を無視して管理画面に固定
        if ((int)$user->is_admin === 1) {
            $request->session()->forget('url.intended');
            return redirect()->route('admin.attendance.index');
        }

        // ✅ 一般ユーザーのみ intended を使う（なければ /attendance）
        return redirect()->intended(route('attendance.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'ログアウトしました。');
    }
}
