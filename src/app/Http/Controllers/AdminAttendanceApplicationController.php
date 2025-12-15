<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceApplication;

class AdminAttendanceApplicationController extends Controller
{
    ##管理者の申請一覧画面の表示
    public function list()
    {
        // 承認待ち（全ユーザー）
        $pending = AttendanceApplication::with('user', 'attendance')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // 承認済み（全ユーザー）
        $approved = AttendanceApplication::with('user', 'attendance')
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.applications.index', compact('pending', 'approved'));
    }

    public function detail($id)
    {
        $application = AttendanceApplication::with('user', 'attendance', 'applicationBreaks')->findOrFail($id);

        return view('admin.attendance.approve', compact('application'));
    }

    public function approve($id)
    {
        $application = AttendanceApplication::with('attendance', 'applicationBreaks')->findOrFail($id);

        $application->status = 'approved';
        $application->save();

        $attendance = $application->attendance;
        $attendance->remarks   = $application->remarks;
        $attendance->clock_in  = $application->clock_in;
        $attendance->clock_out = $application->clock_out;
        $attendance->save();

        $attendance->breaks()->delete();

        foreach ($application->applicationBreaks as $appBreak) {
            $attendance->breaks()->create([
                'break_start' => $appBreak->break_start,
                'break_end'   => $appBreak->break_end,
            ]);
        }

        return redirect()->route('admin.application.detail', ['id' => $id]);
    }
}
