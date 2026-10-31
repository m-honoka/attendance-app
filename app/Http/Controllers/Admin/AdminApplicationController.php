<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\DB;

class AdminApplicationController extends Controller
{
    /**
     * 修正申請一覧を表示する
     */
    public function index()
    {
        // 修正申請と、申請者・対象の勤怠データを取得する
        $applications = StampCorrectionRequest::with([
            'user',
            'attendanceRecord',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 管理者の修正申請一覧画面にデータを渡す
        return view('admin.admin-application-list', compact('applications'));
    }

    /**
     * 修正申請の詳細を表示する
     */
    public function show($id)
    {
        // 申請者・対象勤怠・修正申請の休憩データを取得する
        $application = StampCorrectionRequest::with([
            'user',
            'attendanceRecord',
            'breaks',
        ])->findOrFail($id);

        // 詳細画面にデータを渡す
        return view('admin.admin-application-detail', compact('application'));
    }

    /**
     * 修正申請を承認する
     */
    public function approve($id)
    {
        // 修正申請と関連データを取得する
        $application = StampCorrectionRequest::with([
            'attendanceRecord',
            'breaks',
        ])->findOrFail($id);

        // すでに承認済みの場合は処理しない
        if ($application->status !== 'pending') {
            return redirect()
                ->route('admin.application.show', $id)
                ->with('error', 'この修正申請はすでに承認されています。');
        }

        // 勤怠データと申請データをまとめて更新する
        DB::transaction(function () use ($application) {

            // 修正対象の勤怠データを取得する
            $attendanceRecord = $application->attendanceRecord;

            // 出勤時間・退勤時間を申請内容に更新する
            $attendanceRecord->update([
                'clock_in' => $application->clock_in,
                'clock_out' => $application->clock_out,
            ]);

            // 既存の休憩データを削除する
            $attendanceRecord->breakTimes()->delete();

            // 申請された休憩データを登録する
            foreach ($application->breaks as $break) {
                $attendanceRecord->breakTimes()->create([
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                ]);
            }

            // 修正申請のステータスを承認済みに変更する
            $application->update([
                'status' => 'approved',
            ]);
        });

        // 承認後、詳細画面に戻る
        return redirect()
            ->route('admin.application.show', $id)
            ->with('success', '修正申請を承認しました。');
    }
}
