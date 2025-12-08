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

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController as AdminAttendanceCorrectionRequestController;

/*
|--------------------------------------------------------------------------
| 初回メール認証（登録時のみ）
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| 公開ルート（勤怠とは別の既存フリマ機能）
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('welcome'));
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('/purchase/success', [PurchaseController::class, 'success'])->name('purchase.success');

/*
|--------------------------------------------------------------------------
| 一般ユーザー向け 勤怠機能
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // PG03 勤怠一覧画面（一般ユーザー） /attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    // PG04 勤怠詳細画面（一般ユーザー） /attendance/detail/{id}
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    // 打刻ボタン（出勤 / 退勤 / 休憩開始 / 休憩終了）
    // フォームから mode=work_start 等を送るイメージ
    Route::post('/attendance/stamp', [AttendanceController::class, 'stamp'])
        ->name('attendance.stamp');

    // PG05 申請一覧画面（一般ユーザー） /stamp_correction_request/list
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index'])
        ->name('stamp_correction_request.index');

    // PG06 申請詳細画面（一般ユーザー） /stamp_correction_request/detail/{id}
    Route::get('/stamp_correction_request/detail/{request}', [AttendanceCorrectionRequestController::class, 'show'])
        ->name('stamp_correction_request.show');

    // 新規申請作成（画面）
    Route::get('/stamp_correction_request/create/{attendance}', [AttendanceCorrectionRequestController::class, 'create'])
        ->name('stamp_correction_request.create');

    // 新規申請登録
    Route::post('/stamp_correction_request', [AttendanceCorrectionRequestController::class, 'store'])
        ->name('stamp_correction_request.store');
});

/*
|--------------------------------------------------------------------------
| 管理者向け 勤怠機能
|--------------------------------------------------------------------------
|
| ※ admin 判定ロジックは後で実装します。
|   ひとまず auth, verified のみで進めて、あとで 'can:admin' などに置き換え予定。
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified']) // TODO: ここに admin 用ミドルウェアを追加
    ->group(function () {

        // PG07 ログイン画面（管理者）
        // -> Fortify の /login を使い回す想定なら何もしなくてOK
        //   別画面にするなら /admin/login 用ルートを追加する

        // PG08 勤怠一覧画面（管理者） /admin/attendance/list
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
            ->name('attendance.index');

        // PG09 勤怠詳細画面（管理者） /admin/attendance/detail/{id}
        Route::get('/attendance/detail/{attendance}', [AdminAttendanceController::class, 'show'])
            ->name('attendance.show');

        // PG10 スタッフ一覧画面（管理者） /admin/staff/list
        Route::get('/staff/list', [AdminStaffController::class, 'index'])
            ->name('staff.index');

        // PG11 スタッフ別勤怠一覧画面（管理者） /admin/attendance/staff/{id}
        Route::get('/attendance/staff/{user}', [AdminAttendanceController::class, 'staffAttendance'])
            ->name('attendance.staff');

        // PG12 修正申請一覧画面（管理者） /admin/stamp_correction_request/list
        Route::get('/stamp_correction_request/list', [AdminAttendanceCorrectionRequestController::class, 'index'])
            ->name('stamp_correction_request.index');

        // PG13 修正申請詳細画面（管理者） /admin/stamp_correction_request/detail/{id}
        Route::get('/stamp_correction_request/detail/{request}', [AdminAttendanceCorrectionRequestController::class, 'show'])
            ->name('stamp_correction_request.show');

        // 承認 / 否認ボタン
        Route::post('/stamp_correction_request/{request}/approve', [AdminAttendanceCorrectionRequestController::class, 'approve'])
            ->name('stamp_correction_request.approve');

        Route::post('/stamp_correction_request/{request}/reject', [AdminAttendanceCorrectionRequestController::class, 'reject'])
            ->name('stamp_correction_request.reject');

        // CSV 出力機能
        Route::get('/attendance/csv/export', [AdminAttendanceController::class, 'exportCsv'])
            ->name('attendance.export_csv');
    });

/*
|--------------------------------------------------------------------------
| 認証＋verified 必須の既存フリマ機能
|--------------------------------------------------------------------------
*/
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
    Route::get ('/items/{item}/buy',      [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/items/{item}/buy',      [PurchaseController::class, 'store'])->name('purchase.store');
    Route::post('/items/{item}/checkout', [PurchaseController::class, 'checkout'])->name('purchase.checkout');

    // 住所編集
    Route::get ('/items/{item}/address/edit', [AddressController::class, 'edit'])->name('address.edit');
    Route::post('/items/{item}/address',      [AddressController::class, 'update'])->name('address.update');
});
