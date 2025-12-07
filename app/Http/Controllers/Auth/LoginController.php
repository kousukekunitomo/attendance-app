<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /** ログイン画面表示 */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /** ログイン処理：登録時のみメール認証、ログイン時は送らない */
    public function login(Request $request)
    {
        $credentials = $request->validate(
            [
                'email'    => ['required','string','email'],
                'password' => ['required','string','min:8'],
            ],
            [
                'email.required'    => 'メールアドレスを入力してください',
                'email.email'       => 'メールアドレスはメール形式で入力してください',
                'password.required' => 'パスワードを入力してください',
                'password.min'      => 'パスワードは8文字以上で入力してください',
            ]
        );

        $remember = $request->boolean('remember', false);

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ])->redirectTo(route('login'));
        }

        // セッション再生成
        $request->session()->regenerate();

        // 未認証なら再送はしない。通知ページへ誘導のみ。
        $user = $request->user();
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 認証済みなら通常どおり
        return redirect()->intended(route('items.index'));
    }

    /** ログアウト */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('status', 'ログアウトしました。');
    }
}
