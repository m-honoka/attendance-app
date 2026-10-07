<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        if (!$user1)
            return;

        // --- 1. 過去5ヶ月間：各月平日15日 = 計75日の通常勤務 (09:00 - 18:00) ---
        for ($m = 5; $m >= 1; $m--) {
            $monthDate = Carbon::now()->subMonths($m)->startOfMonth();
            $count = 0;

            while ($count < 15) {
                // 平日のみ対象
                if (!$monthDate->isWeekend()) {
                    $this->createAttendanceAndBreak($user1->id, $monthDate->format('Y-m-d'), '09:00:00', '18:00:00');
                    $count++;
                }
                $monthDate->addDay();
            }
        }

        // --- 2. 当月：17日分の指定データ ---
        $patterns = [
            // 通常勤務 10日 (09:00 - 18:00)
            ...array_fill(0, 10, ['in' => '09:00:00', 'out' => '18:00:00']),
            // 残業 3日 (09:00 - 20:00)
            ...array_fill(0, 3, ['in' => '09:00:00', 'out' => '20:00:00']),
            // 遅刻 2日 (09:30 - 18:00)
            ...array_fill(0, 2, ['in' => '09:30:00', 'out' => '18:00:00']),
            // 早退 1日 (09:00 - 17:00)
            ...array_fill(0, 1, ['in' => '09:00:00', 'out' => '17:00:00']),
            // 長時間労働 1日 (08:00 - 21:00)
            ...array_fill(0, 1, ['in' => '08:00:00', 'out' => '21:00:00']),
        ];

        $currentMonthDate = Carbon::now()->startOfMonth();
        foreach ($patterns as $p) {
            // 平日のみ進める
            while ($currentMonthDate->isWeekend()) {
                $currentMonthDate->addDay();
            }

            $this->createAttendanceAndBreak($user1->id, $currentMonthDate->format('Y-m-d'), $p['in'], $p['out']);
            $currentMonthDate->addDay();
        }
    }

    // 勤怠と固定休憩（12:00〜13:00）をセットで作成するヘルパー関数
    private function createAttendanceAndBreak(int $userId, string $date, string $clockIn, string $clockOut): void
    {
        $attendance = AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => '退勤済',
        ]);

        BreakTime::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}



