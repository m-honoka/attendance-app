<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminStaffController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページ
Route::get('/', function () {
    return view('welcome');
});

// ----------------------------------------------------
// 1. ゲスト（未ログイン）専用ルート
// ----------------------------------------------------
Route::middleware('guest')->group(function () {
    // 管理者用ログイン画面の表示
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    });
});

// ----------------------------------------------------
// 2. 管理者専用ルート（ログイン必須 ＋ 管理者権限必須）
// ----------------------------------------------------
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {

    // 管理者 勤怠一覧画面 (URL: /admin/attendance/list, Route名: admin.attendance.list)
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('attendance.list');

    // 管理者 勤怠詳細画面
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('attendance.show');

    // 管理者 勤怠情報の修正
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('attendance.update');

    // スタッフ一覧画面
    Route::get('/staff/list', [AdminStaffController::class, 'index'])
        ->name('staff.list');

    // スタッフごとの勤怠一覧画面
    Route::get(
        '/attendance/staff/{id}',
        [AdminStaffController::class, 'show']
    )->name('staff.attendance');

    // 修正申請一覧画面 (URL: /admin/stamp_correction_request/list, Route名: admin.application.list)
    Route::get('/stamp_correction_request/list', [AdminApplicationController::class, 'index'])
        ->name('application.list');

    // 修正申請の詳細画面
    Route::get('/stamp_correction_request/approve/{id}', [AdminApplicationController::class, 'show'])
        ->name('application.show');

    // 修正申請を承認する
    Route::post(
        '/stamp_correction_request/approve/{id}',
        [AdminApplicationController::class, 'approve']
    )->name('application.approve');
});

// ----------------------------------------------------
// 3. 一般ユーザー専用ルート（ログイン必須）
// ----------------------------------------------------
Route::middleware('auth')->group(function () {

    // 一般ユーザーの勤怠打刻画面の表示
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    // 出勤打刻
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');

    // 休憩開始
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])
        ->name('attendance.breakIn');

    // 休憩終了
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])
        ->name('attendance.breakOut');

    // 退勤打刻
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');

    //　勤怠一覧画面の表示    
    Route::get('/attendance/list', [AttendanceController::class, 'list'])
        ->name('attendance.list');

    // 勤怠詳細画面の表示
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    // 勤怠修正申請
    Route::post('/attendance/{id}', [AttendanceController::class, 'storeCorrectionRequest'])
        ->name('attendance.correction.store');
});