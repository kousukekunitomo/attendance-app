<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController as AdminAttendanceCorrectionRequestController;

/*
|--------------------------------------------------------------------------
| 公開ルート（ログイン前）
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('welcome'));

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register');

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| メール認証（登録後）
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn () => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()
            ->route('attendance.index')
            ->with('status', 'メール認証が完了しました。');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', '認証メールを再送しました。');
    })->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー向け 勤怠機能
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/stamp', [AttendanceController::class, 'stamp'])->name('attendance.stamp');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');

    Route::get('/attendance/detail/date/{date}', [AttendanceController::class, 'showByDate'])
        ->name('attendance.show_by_date');

    Route::patch('/attendance/detail/{attendance}', [AttendanceController::class, 'update'])
        ->name('attendance.update');

    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index'])
        ->name('stamp_correction_request.index');

    Route::get('/stamp_correction_request/detail/{stamp_correction_request}', [AttendanceCorrectionRequestController::class, 'show'])
        ->name('stamp_correction_request.show');

    Route::get('/stamp_correction_request/create/{attendance}', [AttendanceCorrectionRequestController::class, 'create'])
        ->name('stamp_correction_request.create');

    Route::post('/stamp_correction_request', [AttendanceCorrectionRequestController::class, 'store'])
        ->name('stamp_correction_request.store');
});

/*
|--------------------------------------------------------------------------
| 管理者向け
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin']) // ← verified を削除
    ->group(function () {

        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
            ->name('attendance.index');

        Route::get('/attendance/detail/{attendance}', [AdminAttendanceController::class, 'show'])
            ->name('attendance.show');

        Route::put('/attendance/detail/{attendance}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');

        Route::get('/attendance/staff/{user}', [AdminAttendanceController::class, 'staffAttendance'])
            ->name('attendance.staff');

        Route::get('/staff/list', [AdminStaffController::class, 'index'])
            ->name('staff.index');

        Route::get('/stamp_correction_request/list', [AdminAttendanceCorrectionRequestController::class, 'index'])
            ->name('stamp_correction_request.index');

        Route::get('/stamp_correction_request/detail/{request}', [AdminAttendanceCorrectionRequestController::class, 'show'])
            ->name('stamp_correction_request.show');

        Route::post('/stamp_correction_request/{request}/approve', [AdminAttendanceCorrectionRequestController::class, 'approve'])
            ->name('stamp_correction_request.approve');

        Route::post('/stamp_correction_request/{request}/reject', [AdminAttendanceCorrectionRequestController::class, 'reject'])
            ->name('stamp_correction_request.reject');

        Route::get('/attendance/csv/export', [AdminAttendanceController::class, 'exportCsv'])
            ->name('attendance.export_csv');

        Route::get('/attendance/detail/date/{user}/{date}', [AdminAttendanceController::class, 'showByDate'])
            ->name('attendance.show_by_date');
    });