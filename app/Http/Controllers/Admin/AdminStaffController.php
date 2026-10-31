<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧を表示する
     */
    public function index()
    {
        // 管理者を除いた一般ユーザーを取得する
        $staffs = User::where('admin_status', 0)
            ->orderBy('id', 'asc')
            ->get();

        // スタッフ一覧画面にデータを渡す
        return view('admin.staff-list', compact('staffs'));
    }

    /**
     * スタッフごとの月次勤怠一覧を表示する
     */
    public function show(Request $request, $id)
    {
        // 対象スタッフを取得する（管理者は除外）
        $user = User::where('admin_status', 0)
            ->findOrFail($id);

        // 表示対象の年月を取得する
        $monthParam = $request->query('date');

        // 年月の形式を検証する
        if (
            !is_string($monthParam) ||
            !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)
        ) {
            $monthParam = Carbon::now()->format('Y-m');
        }

        // 年月文字列をCarbonに変換する
        $date = Carbon::createFromFormat('!Y-m', $monthParam);

        // 前月・翌月を取得する
        $previousMonth = $date->copy()
            ->subMonth()
            ->format('Y-m');

        $nextMonth = $date->copy()
            ->addMonth()
            ->format('Y-m');

        // 対象スタッフの対象月の勤怠データを取得する
        $attendances = AttendanceRecord::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'asc')
            ->get();

        // 休憩時間・勤務時間を計算する
        foreach ($attendances as $attendance) {

            $totalBreakMinutes = 0;

            foreach ($attendance->breakTimes as $break) {

                if ($break->break_in && $break->break_out) {

                    $breakIn = Carbon::createFromFormat('H:i:s', $break->break_in);
                    $breakOut = Carbon::createFromFormat('H:i:s', $break->break_out);

                    if ($breakOut->greaterThanOrEqualTo($breakIn)) {
                        $totalBreakMinutes += $breakIn->diffInMinutes($breakOut);
                    }
                }
            }

            $totalWorkMinutes = 0;

            if ($attendance->clock_in && $attendance->clock_out) {

                $clockIn = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
                $clockOut = Carbon::createFromFormat('H:i:s', $attendance->clock_out);

                if ($clockOut->greaterThanOrEqualTo($clockIn)) {

                    $grossWorkMinutes = $clockIn->diffInMinutes($clockOut);

                    $totalWorkMinutes = max(
                        0,
                        $grossWorkMinutes - $totalBreakMinutes
                    );
                }
            }

            // 表示用の時間をHH:mm形式に変換する
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

        // Bladeで使用する表示用データを作成する
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

        // スタッフごとの勤怠一覧画面にデータを渡す
        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }
}