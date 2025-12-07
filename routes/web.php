<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ItemCommentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AddressController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/* ============================
 * 初回メール認証（登録時のみ）
 * ============================ */
Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    $user = $request->user();

    // セッション or DB のどちらかに初回フラグがあればプロフィール編集へ
    $fromSession = (bool) $request->session()->pull('after_register', false);
    $fromDb      = (bool) ($user->needs_profile_setup ?? false);
    $goProfile   = $fromSession || $fromDb;

    return redirect()
        ->route($goProfile ? 'profile.edit' : 'items.index')
        ->with('status', 'メール認証が完了しました。');
})->middleware(['auth','signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->route('items.index');
    }
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', '認証メールを再送しました。');
})->middleware(['auth','throttle:6,1'])->name('verification.send');

/* ===== 公開 ===== */
Route::get('/', fn () => view('welcome'));
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('/purchase/success', [PurchaseController::class, 'success'])->name('purchase.success');

/* ===== 認証＋verified 必須の領域 ===== */
Route::middleware(['auth', 'verified'])->group(function () {
    // プロフィール
    Route::get('/profile/edit',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // 出品
    Route::get('/sell',  [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');

    // マイページ
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage.index');

    // いいね
    Route::post('/items/{item}/like', [LikeController::class, 'toggle'])->name('items.like.toggle');

    // コメント
    Route::post('/items/{item}/comments', [ItemCommentController::class, 'store'])->name('items.comments.store');

    // 購入
    Route::get ('/items/{item}/buy',     [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/items/{item}/buy',     [PurchaseController::class, 'store'])->name('purchase.store');
    Route::post('/items/{item}/checkout',[PurchaseController::class, 'checkout'])->name('purchase.checkout');

    // 住所編集
    Route::get ('/items/{item}/address/edit', [AddressController::class, 'edit'])->name('address.edit');
    Route::post('/items/{item}/address',      [AddressController::class, 'update'])->name('address.update');
});
