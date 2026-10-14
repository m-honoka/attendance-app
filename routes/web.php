<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;

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
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');

    // スタッフ一覧画面 (URL: /admin/staff/list, Route名: admin.staff.list)
    Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');

});

// ----------------------------------------------------
// 3. 一般ユーザー専用ルート（ログイン必須）
// ----------------------------------------------------
Route::middleware('auth')->group(function () {

    // 一般ユーザーの勤怠打刻画面
    Route::get('/attendance', function () {
        return view('user.attendance-register', [
            'user' => auth()->user(),
            'formattedDate' => Carbon::now()->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => Carbon::now()->format('H:i'),
        ]);
    })->name('attendance.register');

});