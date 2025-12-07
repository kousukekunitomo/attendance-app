<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Contract ←→ 実装 をバインド
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
        $this->app->singleton(LoginResponseContract::class,     LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class,  RegisterResponse::class);
        $this->app->singleton(LogoutResponseContract::class,    LogoutResponse::class);
    }

    public function boot(): void
    {
        // ビュー
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));

        // ログインのレート制限（5回/分）
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(($request->email ?? 'guest').'|'.$request->ip());
        });

        // ログイン処理（LoginRequest のルール/メッセージを再利用）
        Fortify::authenticateUsing(function (Request $request) {
            $form       = app(LoginRequest::class);
            $rules      = $form->rules();
            $messages   = method_exists($form, 'messages')   ? $form->messages()   : [];
            $attributes = method_exists($form, 'attributes') ? $form->attributes() : [];

            $validated = Validator::make($request->all(), $rules, $messages, $attributes)->validate();

            $user = User::where('email', $validated['email'])->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }
            return $user;
        });
    }
}
