<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 打刻画面（勤怠登録画面）の表示
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 本日の勤怠レコードを取得
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 現在のステータス判定（「勤務外」「出勤中」「休憩中」「退勤済」）
        $status = '勤務外';
        if ($attendance) {
            $status = $attendance->status;
        }

        $now = Carbon::now();
        // 表示用の日付・時刻に整形
        $formattedDate = $now->format('Y年m月d日');
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact('attendance', 'status', 'now', 'formattedDate', 'formattedTime'));
    }

    /**
     * 出勤打刻処理
     */
    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 1日1回のみ登録可能
        if (!$attendance) {
            AttendanceRecord::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $now->toTimeString(),
                'status' => '出勤中',
            ]);
        }

        return redirect()->back();
    }

    /**
     * 休憩入打刻処理
     */
    public function breakIn()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance && $attendance->status === '出勤中') {
            // ステータスを「休憩中」に更新
            $attendance->update(['status' => '休憩中']);

            // 休憩レコードの新規作成
            BreakTime::create([
                'attendance_record_id' => $attendance->id,
                'break_in' => $now->toTimeString(),
            ]);
        }

        return redirect()->back();
    }

    /**
     * 休憩戻打刻処理
     */
    public function breakOut()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance && $attendance->status === '休憩中') {
            // 直近の未終了休憩レコード（break_outがnull）を取得して終了時刻を記録
            $latestBreak = BreakTime::where('attendance_record_id', $attendance->id)
                ->whereNull('break_out')
                ->latest()
                ->first();

            if ($latestBreak) {
                $latestBreak->update(['break_out' => $now->toTimeString()]);
            }

            // ステータスを「出勤中」に戻す
            $attendance->update(['status' => '出勤中']);
        }

        return redirect()->back();
    }

    /**
     * 退勤打刻処理
     */
    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance && $attendance->status === '出勤中') {
            $attendance->update([
                'clock_out' => $now->toTimeString(),
                'status' => '退勤済',
            ]);
        }

        return redirect()->back()->with('message', 'お疲れ様でした。');
    }

    public function list(Request $request)
    {
        // 1. ログインユーザーを取得
        $user = Auth::user();

        // 2. 表示対象の年月を取得
        // month が指定されていなければ当月を使用
        $monthParam = $request->query('month');

        // 3. 年月の形式を検証
        // 正しい形式：2026-10
        if (
            !is_string($monthParam) ||
            !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)
        ) {
            $monthParam = Carbon::now()->format('Y-m');
        }

        // 4. 年月文字列を Carbon に変換
        $currentMonth = Carbon::createFromFormat('!Y-m', $monthParam);

        // 5. 前月・翌月の年月を取得
        $prevMonth = $currentMonth->copy()
            ->subMonth()
            ->format('Y-m');

        $nextMonth = $currentMonth->copy()
            ->addMonth()
            ->format('Y-m');

        // 6. 対象月の勤怠データを取得
        // 関連する休憩データも同時に取得
        $attendances = AttendanceRecord::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get();

        // 7. 各勤怠レコードの休憩時間・勤務時間を計算
        foreach ($attendances as $attendance) {

            // 休憩時間の合計（分）
            $totalBreakMinutes = 0;

            foreach ($attendance->breakTimes as $break) {

                // 休憩開始・終了の両方が入力されている場合だけ計算
                if ($break->break_in && $break->break_out) {

                    $breakIn = Carbon::createFromFormat(
                        'H:i:s',
                        $break->break_in
                    );

                    $breakOut = Carbon::createFromFormat(
                        'H:i:s',
                        $break->break_out
                    );

                    // 同一日内の休憩時間を計算
                    if ($breakOut->greaterThanOrEqualTo($breakIn)) {
                        $totalBreakMinutes += $breakIn->diffInMinutes($breakOut);
                    }
                }
            }

            // 勤務時間の合計（分）
            $totalWorkMinutes = 0;

            if ($attendance->clock_in && $attendance->clock_out) {

                $clockIn = Carbon::createFromFormat(
                    'H:i:s',
                    $attendance->clock_in
                );

                $clockOut = Carbon::createFromFormat(
                    'H:i:s',
                    $attendance->clock_out
                );

                // 同一日内の勤務時間だけを計算
                if ($clockOut->greaterThanOrEqualTo($clockIn)) {

                    $grossWorkMinutes = $clockIn->diffInMinutes($clockOut);

                    // 休憩時間を引いて実働時間を計算
                    $totalWorkMinutes = max(
                        0,
                        $grossWorkMinutes - $totalBreakMinutes
                    );
                }
            }

            // 8. 表示用の時間を HH:mm 形式に変換
            $attendance->formatted_break_time = sprintf(
                '%02d:%02d',
                intdiv($totalBreakMinutes, 60),
                $totalBreakMinutes % 60
            );

            $attendance->formatted_work_time = sprintf(
                '%02d:%02d',
                intdiv($totalWorkMinutes, 60),
                $totalWorkMinutes % 60
            );
        }

        // Bladeで使用する表示用データを作成
        $formattedAttendanceRecords = $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'total_break_time' => $attendance->formatted_break_time,
                'total_time' => $attendance->formatted_work_time,
            ];
        });

        // 9. 勤怠一覧画面にデータを渡す
        return view('user.user-attendance-list', compact(
            'formattedAttendanceRecords',
            'currentMonth',
            'prevMonth',
            'nextMonth'
        ));
    }

    /**
     * 勤怠詳細画面を表示
     */
    public function show($id)
    {
        // ログインしているユーザーを取得
        $user = Auth::user();

        // ログインユーザー本人の勤怠記録を取得
        // 関連する休憩記録も一緒に取得する
        $attendance = AttendanceRecord::with('breakTimes')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // 対象の勤怠記録に紐づく、承認待ちの修正申請を取得
        $application = $attendance->stampCorrectionRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        // 休憩記録をBlade用の配列に変換
        $breaks = $attendance->breakTimes->map(function ($break) {
            return [
                'break_in' => $break->break_in
                    ? Carbon::parse($break->break_in)->format('H:i')
                    : '',
                'break_out' => $break->break_out
                    ? Carbon::parse($break->break_out)->format('H:i')
                    : '',
            ];
        })->toArray();

        // Bladeが使用する形式にデータを整える
        $data = [
            'id' => $attendance->id,
            'year' => Carbon::parse($attendance->date)->format('Y年'),
            'date' => Carbon::parse($attendance->date)->format('n月j日'),
            'clock_in' => $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '',
            'clock_out' => $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '',
            'breaks' => $breaks,
            'comment' => $attendance->comment ?? '',
            'application' => $application,
        ];

        return view('user.user-detail', compact('user', 'data'));
    }
}


