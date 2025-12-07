<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfGuestTab
{
    /**
     * 「おすすめ（recommend）」以外のタブは未ログインならログイン画面へ
     */
    public function handle(Request $request, Closure $next)
    {
        $tab = $request->query('tab', 'recommend');

        if ($tab !== 'recommend' && !auth()->check()) {
            // ログイン後に元のURLへ戻れるよう保存
            session(['url.intended' => $request->fullUrl()]);
            return redirect()->route('login');
        }

        return $next($request);
    }
}
