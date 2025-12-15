<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceApplicationController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminAttendanceApplicationController;



Route::get('/', function () {
    return redirect('/login');
});

##会員登録画面
Route::post('/register', [AuthController::class, 'register']);

##ログイン画面(一般ユーザー)
Route::post('/login', [AuthController::class, 'login']);

// メール認証ページ
Route::get('/email/verify', [AuthController::class, 'notice'])
    ->name('verification.notice');

// メール内リンククリック時（認証完了）
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

// 認証メール再送
Route::post('/email/verification-notification', [AuthController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

// 一般ユーザー認証
Route::middleware(['auth', 'verified'])->group(
    function () {
        ##ログアウト
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        ##出勤登録画面
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/clock', [AttendanceController::class, 'clock'])->name('attendance.clock');

        ##勤怠一覧画面
        Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

        ##勤怠詳細画面
        Route::get('/attendance/detail/{id?}', [AttendanceController::class, 'detail'])->name('attendance.detail');

        ##勤怠新規登録申請
        Route::post('/attendance/store/{id}', [AttendanceController::class, 'store'])->name('attendance.store');

        ##勤怠更新申請
        Route::post('/attendance/update/{id}', [AttendanceController::class, 'update'])->name('attendance.update');

        ##申請一覧画面
        Route::get('/stamp_correction_request/list', [AttendanceApplicationController::class, 'list'])->name('application.list');
    }
);

// 管理者認証
Route::prefix('admin')->name('admin.')->group(function () {

    ##ログイン画面(管理者)
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware('auth:admin')->group(function () {

        ##ログアウト
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        ##スタッフの勤怠一覧画面
        Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('attendance.list');

        ##勤怠詳細画面
        Route::get('/attendance/{id?}', [AdminAttendanceController::class, 'detail'])->name('attendance.detail');

        ##勤怠新規登録申請
        Route::post('/attendance/store/{id}', [AdminAttendanceController::class, 'store'])
            ->name('attendance.store');

        ##勤怠更新申請
        Route::post('/attendance/update/{id}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');

        ##スタッフ一覧画面
        Route::get('/staff/list', [AdminStaffController::class, 'list']);

        ##スタッフ別勤怠一覧画面
        Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('attendance.staff');

        ##CSV出力
        Route::get(
            '/attendance/staff/{id}/csv',
            [AdminAttendanceController::class, 'exportCsv']
        )->name('attendance.staff.csv');

        ##申請一覧画面
        Route::get(
            '/stamp_correction_request/list',
            [AdminAttendanceApplicationController::class, 'list']
        )->name('application.list');

        Route::get(
            '/stamp_correction_request/detail/{id}',
            [AdminAttendanceApplicationController::class, 'detail']
        )->name('application.detail');

        Route::post(
            '/stamp_correction_request/approve/{attendance_correct_request_id}',
            [AdminAttendanceApplicationController::class, 'approve']
        )->name('application.approve');
    });
});
