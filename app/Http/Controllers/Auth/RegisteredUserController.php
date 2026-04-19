<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisteredUserController extends Controller
{
    /**
     * 新規登録フォーム表示 (GET /register)
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * 新規登録処理 (POST /register)
     */
    public function store(
        RegisterRequest $request,
        CreateNewUser $creator
    ): RegisterResponseContract {
        $user = $creator->create($request->validated());

        Auth::login($user);

        Log::info('register:created-user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'auth_id' => Auth::id(),
            'auth_email' => Auth::user()?->email,
        ]);

        try {
            // 作成したユーザー本人に送る
            $user->sendEmailVerificationNotification();

            Log::info('register:verification-mail-sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('register:verification-mail-failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return app(RegisterResponseContract::class);
    }
}