<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginChallengeMail;
use App\Models\LoginChallenge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoginChallengeController extends Controller
{
    /** 「メール送信」画面（未ログインで閲覧可） */
    public function sent(Request $request)
    {
        return view('auth.login-challenge-sent');
    }

    /** メール内リンクで検証してログイン確定 */
    public function verify(Request $request)
    {
        $token = $request->query('token');
        $challenge = LoginChallenge::where('token', $token)->first();

        if (! $challenge || ! $challenge->isValid()) {
            return redirect()->route('login')->withErrors([
                'email' => 'リンクが無効か期限切れです。再度ログインしてください。',
            ]);
        }

        // 一度きりに
        $challenge->update(['used_at' => now()]);

        // ログイン確定
        Auth::loginUsingId($challenge->user_id);
        $request->session()->regenerate();

        // 商品一覧へ
        return redirect()->route('items.index');
    }

    /** 認証メールの再送（/login/challenge/resend） */
    public function resend(Request $request)
    {
        // LoginController で保存した user_id を使う
        $userId = $request->session()->get('login.challenge_user_id');
        if (! $userId) {
            return redirect()->route('login')->withErrors([
                'email' => '再送できませんでした。もう一度ログインしてください。',
            ]);
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => '再送できませんでした。もう一度ログインしてください。',
            ]);
        }

        // 既存の未使用チャレンジを失効
        LoginChallenge::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // 新しいチャレンジを発行（10分有効）
        $challenge = LoginChallenge::create([
            'user_id'    => $user->id,
            'token'      => Str::random(64),
            'ip'         => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'expires_at' => now()->addMinutes(10),
        ]);

        // メール送信
        Mail::to($user->email)->send(new LoginChallengeMail($challenge));

        // そのまま画面に戻る（文言は出さない）
        return back();
    }
}
