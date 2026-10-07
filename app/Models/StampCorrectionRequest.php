<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'clock_in',
        'clock_out',
        'status',
        'reason',
    ];

    // 申請した User を取得
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 修正対象の AttendanceRecord を取得
    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
