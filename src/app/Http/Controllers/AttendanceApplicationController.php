<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceApplication;

class AttendanceApplicationController extends Controller
{
    ##申請一覧画面の表示
    public function list()
    {
        $userId = auth()->id();

        // 承認待ち
        $pending = AttendanceApplication::with('user', 'attendance')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // 承認済み
        $approved = AttendanceApplication::with('user', 'attendance')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.applications.index', compact('pending', 'approved'));
    }
}
