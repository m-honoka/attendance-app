<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';


    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'comment',
    ];

    // 紐ずく User を取得 (多対1)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // 紐ずく複数の Breaks を取得 (1対多)
    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class, 'attendance_record_id');
    }
    // 紐ずく複数の打刻修正申請を取得 (1対多)
    public function stampCorrectionRequests(): HasMany
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }
}
