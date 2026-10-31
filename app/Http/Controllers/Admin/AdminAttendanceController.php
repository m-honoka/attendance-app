<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者用の勤怠一覧画面を表示
     */
    public function index(Request $request)
    {
        // 1. 表示対象の日付を取得
        // date が指定されていなければ今日の日付を使用
        $dateParam = $request->query('date');

        // 2. 日付の形式を検証
        if (
            !is_string($dateParam) ||
            !preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $dateParam) ||
            !Carbon::hasFormat($dateParam, 'Y-m-d')
        ) {
            $dateParam = Carbon::today()->format('Y-m-d');
        }

        // 3. 日付を Carbon に変換
        $date = Carbon::createFromFormat('!Y-m-d', $dateParam);

        // 4. 前日・翌日の日付を取得
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 5. スタッフ一覧を取得
        $users = User::orderBy('id', 'asc')->get();

        // 6. 指定日の勤怠データと休憩データを取得
        $attendanceRecords = AttendanceRecord::with('breakTimes')
            ->whereDate('date', $date->format('Y-m-d'))
            ->get();

        // 7. 各勤怠データの休憩時間・勤務時間を計算
        foreach ($attendanceRecords as $attendance) {
            $totalBreakMinutes = 0;

            foreach ($attendance->breakTimes as $break) {
                if ($break->break_in && $break->break_out) {
                    $breakIn = Carbon::parse($break->break_in);
                    $breakOut = Carbon::parse($break->break_out);

                    if ($breakOut->greaterThanOrEqualTo($breakIn)) {
                        $totalBreakMinutes += $breakIn->diffInMinutes($breakOut);
                    }
                }
            }

            $totalWorkMinutes = 0;

            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                if ($clockOut->greaterThanOrEqualTo($clockIn)) {
                    $grossWorkMinutes = $clockIn->diffInMinutes($clockOut);

                    $totalWorkMinutes = max(
                        0,
                        $grossWorkMinutes - $totalBreakMinutes
                    );
                }
            }

            // Blade が期待する時間形式に変換
            $attendance->total_break_time = sprintf(
                '%02d:%02d',
                intdiv($totalBreakMinutes, 60),
                $totalBreakMinutes % 60
            );

            $attendance->total_time = sprintf(
                '%02d:%02d',
                intdiv($totalWorkMinutes, 60),
                $totalWorkMinutes % 60
            );
        }

        // 8. Blade にデータを渡す
        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    /**
     * 管理者用の勤怠詳細画面を表示
     */
    public function show($id)
    {
        // 1. 指定されたIDの勤怠データを取得
        // 休憩情報も一緒に取得する
        $attendanceRecord = AttendanceRecord::with('breakTimes')
            ->findOrFail($id);

        // 2. 勤怠データに紐づくユーザーを取得
        $user = $attendanceRecord->user;

        // 3. Bladeで表示するデータを整理
        $breaks = $attendanceRecord->breakTimes->map(function ($break) {
            return [
                'break_in' => $break->break_in,
                'break_out' => $break->break_out,
            ];
        })->toArray();

        // 4. Bladeが使用する形式に変換
        $attendanceRecordData = [
            'id' => $attendanceRecord->id,
            'year' => \Carbon\Carbon::parse($attendanceRecord->date)->format('Y年'),
            'date' => \Carbon\Carbon::parse($attendanceRecord->date)->format('m月d日'),
            'clock_in' => $attendanceRecord->clock_in,
            'clock_out' => $attendanceRecord->clock_out,
            'breaks' => $breaks,
            'comment' => $attendanceRecord->comment,
        ];

        // 5. 勤怠詳細画面にデータを渡す
        return view('admin.admin-detail', [
            'user' => $user,
            'attendanceRecord' => $attendanceRecordData,
        ]);
    }

    /**
     * 管理者による勤怠情報の更新
     */
    public function update(Request $request, $id)
    {
        // 1. 入力値を検証
        $validated = $request->validate([
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['nullable', 'date_format:H:i', 'after_or_equal:new_clock_in'],
            'new_break_in' => ['nullable', 'array'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out' => ['nullable', 'array'],
            'new_break_out.*' => [
                'nullable',
                'date_format:H:i',
                'required_with:new_break_in.*',
            ],
            'comment' => ['required', 'string', 'max:255'],
        ], [
            'new_clock_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ]);

        // 休憩時間と退勤時間の前後関係を検証
        $clockOut = $validated['new_clock_out'] ?? null;
        $breakIns = $validated['new_break_in'] ?? [];
        $breakOuts = $validated['new_break_out'] ?? [];

        if ($clockOut !== null) {
            foreach ($breakIns as $index => $breakIn) {
                // 休憩開始時間が未入力の場合はスキップ
                if (empty($breakIn)) {
                    continue;
                }

                // 休憩開始時間が退勤時間より後の場合
                if ($breakIn > $clockOut) {
                    return back()
                        ->withErrors([
                            'new_break_in.' . $index =>
                                '休憩時間が不適切な値です',
                        ])
                        ->withInput();
                }

                $breakOut = $breakOuts[$index] ?? null;

                // 休憩終了時間が未入力の場合はスキップ
                if (empty($breakOut)) {
                    continue;
                }

                // 休憩終了時間が退勤時間より後の場合
                if ($breakOut > $clockOut) {
                    return back()
                        ->withErrors([
                            'new_break_out.' . $index =>
                                '休憩時間もしくは退勤時間が不適切な値です',
                        ])
                        ->withInput();
                }
            }
        }

        // 2. 対象の勤怠データを取得
        $attendanceRecord = AttendanceRecord::with('breakTimes')
            ->findOrFail($id);

        // 3. 勤怠情報と休憩情報をまとめて更新
        DB::transaction(function () use ($attendanceRecord, $validated) {

            // 出勤・退勤時刻と備考を更新
            $attendanceRecord->clock_in = $validated['new_clock_in'];
            $attendanceRecord->clock_out = $validated['new_clock_out'] ?? null;
            $attendanceRecord->comment = $validated['comment'];
            $attendanceRecord->save();

            // 既存の休憩データを削除
            $attendanceRecord->breakTimes()->delete();

            // 送信された休憩データを保存
            $breakIns = $validated['new_break_in'] ?? [];
            $breakOuts = $validated['new_break_out'] ?? [];

            foreach ($breakIns as $index => $breakIn) {
                // 休憩開始時刻が空欄なら保存しない
                if (empty($breakIn)) {
                    continue;
                }

                $breakOut = $breakOuts[$index] ?? null;

                // 休憩開始・終了時刻を保存
                $attendanceRecord->breakTimes()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });

        // 4. 更新後、管理者の勤怠詳細画面に戻る
        return redirect()
            ->route('admin.attendance.show', $id)
            ->with('status', '勤怠情報を更新しました。');
    }
}