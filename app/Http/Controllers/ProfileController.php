<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $profile = $user->profile;

        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(ProfileRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        // ユーザー名更新
        $user->name = $validated['name'];
        $user->save();

        $profileData = $validated;

        // 画像アップロード処理
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            $profileData['profile_image'] = $path;
        } else {
            unset($profileData['profile_image']);
        }

        // profile テーブルを更新または作成
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        // --- 初回判定（セッション or DB） ---
        $afterRegister = $request->session()->pull('after_register', false);

        $wasSetupNeeded = (bool) ($user->needs_profile_setup ?? false);
        if ($wasSetupNeeded) {
            $user->forceFill(['needs_profile_setup' => false])->save();
        }

        if ($afterRegister || $wasSetupNeeded) {
            return redirect()
                ->route('items.index')
                ->with('success', 'プロフィールを更新しました。');
        }

        // 通常時はマイページへ
        return redirect()
            ->route('mypage.index')
            ->with('success', 'プロフィールを更新しました。');
    }
}
