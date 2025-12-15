<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationBreakTime extends Model
{
    use HasFactory;

    protected $table = 'application_break_times';

    protected $fillable = [
        'attendance_application_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendanceApplication()
    {
        return $this->belongsTo(AttendanceApplication::class);
    }
}
