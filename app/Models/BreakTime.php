<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    use HasFactory;

    // テーブル名を明示的に指定する（これで 'breaks' テーブルを参照してくれます）
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];

    // 親の AttendanceRecord を取得
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }
}
