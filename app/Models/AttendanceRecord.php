<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'comment',
    ];

    // 紐ずく User を取得
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // 紐ずく複数の Breaks を取得
    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance`record_id');
    }
    // 日もずく修正申請を取得
    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }
}
