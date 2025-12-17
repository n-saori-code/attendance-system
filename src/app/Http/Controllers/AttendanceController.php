<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceApplication;
use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    ##出勤登録画面の表示
    public function index()
    {
        Carbon::setLocale('ja');
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', "=", Auth::id())
            ->whereDate('date', $today)
            ->first();
        return view('users.attendance.create', compact('attendance', 'today'));
    }

    ##勤怠打刻処理
    public function clock(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today]
        );

        $clockTime = Carbon::parse($request->clock_time, 'Asia/Tokyo')->setTimezone('Asia/Tokyo');
        $action = $request->input('action');

        switch ($action) {
            case 'clock_in':
                if (is_null($attendance->clock_in)) {
                    $attendance->clock_in = $clockTime;
                    $attendance->status = '出勤中';
                    $attendance->save();
                }
                break;

            case 'break_start':
                $attendance->breaks()->create([
                    'break_start' => $clockTime,
                ]);
                $attendance->status = '休憩中';
                $attendance->save();
                break;

            case 'break_end':
                $lastBreak = $attendance->breaks()->latest()->first();
                if ($lastBreak && !$lastBreak->break_end) {
                    $lastBreak->break_end = $clockTime;
                    $lastBreak->save();
                }
                $attendance->status = '出勤中';
                $attendance->save();
                break;

            case 'clock_out':
                if (is_null($attendance->clock_out)) {
                    $attendance->clock_out = $clockTime;
                    $attendance->status = '退勤済';
                    $attendance->save();
                }
                break;
        }

        return redirect()->route('attendance.index');
    }

    ##勤怠一覧画面の表示
    public function list(Request $request)
    {
        $user = Auth::user();

        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam . '-01') : Carbon::now();

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        $monthLabel = $currentMonth->format('Y/m');

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($att) => $att->date->format('Y-m-d'));

        $dates = [];
        $date = $startOfMonth->copy();
        while ($date->lte($endOfMonth)) {
            $attendance = $attendances->get($date->format('Y-m-d'));
            $breakTotal = null;
            $workTotal = null;

            if ($attendance) {
                // 休憩合計
                $breakMinutes = $attendance->breaks->reduce(function ($carry, $break) {
                    if ($break->break_start && $break->break_end) {
                        $start = $break->break_start->copy();
                        $end = $break->break_end->copy();

                        // 休憩終了が開始より前なら翌日扱いにする
                        if ($end->lt($start)) {
                            $end->addDay();
                        }

                        // diffInMinutesの引数順を修正（start→end の順に）
                        $diff = $start->diffInMinutes($end);

                        // 万一マイナス計算になるような異常値を防ぐ
                        if ($diff < 0) {
                            $diff = 0;
                        }

                        return $carry + $diff;
                    }
                    return $carry;
                }, 0);

                $breakTotal = $breakMinutes ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60) : null;

                // 勤務時間
                if ($attendance->clock_in && $attendance->clock_out) {
                    $clockIn = $attendance->clock_in->copy();
                    $clockOut = $attendance->clock_out->copy();

                    if ($clockOut->lt($clockIn)) {
                        $clockOut->addDay();
                    }

                    $workMinutes = $clockIn->diffInMinutes($clockOut) - $breakMinutes;
                    $workMinutes = max(0, $workMinutes); // マイナス防止

                    $workTotal = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
                }
            }

            $dates[] = [
                'date' => $date->copy(),
                'attendance' => $attendance,
                'breakTotal' => $breakTotal,
                'workTotal' => $workTotal,
            ];

            $date->addDay();
        }

        return view('users.attendance.index', compact('monthLabel', 'prevMonth', 'nextMonth', 'dates'));
    }

    ##勤怠詳細画面の表示
    public function detail(Request $request, $id = null)
    {
        $user = Auth::user();

        if ($id) {
            $attendance = Attendance::with('breaks')
                ->where('user_id', $user->id)
                ->findOrFail($id);
        } elseif ($request->query('date')) {
            $date = Carbon::parse($request->query('date'));

            $attendance = Attendance::with('breaks')
                ->where('user_id', $user->id)
                ->whereDate('date', $date)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance([
                    'date' => $date,
                ]);
                $attendance->setRelation('breaks', collect());
            }
        } else {
            abort(404);
        }

        // 承認待ち判定（id がある場合のみ）
        $isPending = false;
        if ($attendance->id) {
            $latestApplication = AttendanceApplication::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($latestApplication) {
                $isPending = true;

                $attendance->clock_in  = $latestApplication->clock_in
                    ? Carbon::parse($latestApplication->clock_in)
                    : null;
                $attendance->clock_out = $latestApplication->clock_out
                    ? Carbon::parse($latestApplication->clock_out)
                    : null;
                $attendance->remarks   = $latestApplication->remarks;

                $attendance->setRelation(
                    'breaks',
                    $latestApplication->applicationBreaks->map(function ($break) {
                        $break->break_start = $break->break_start ? Carbon::parse($break->break_start) : null;
                        $break->break_end   = $break->break_end   ? Carbon::parse($break->break_end)   : null;
                        return $break;
                    })
                );
            }
        }

        return view('users.attendance.show', compact('attendance', 'isPending'));
    }

    ##勤怠新規登録申請処理
    public function store(ApplicationRequest $request)
    {
        $user = Auth::user();

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => Carbon::parse($request->date),
            ]
        );

        $application = AttendanceApplication::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'remarks' => $request->remarks,
            'status' => 'pending', // 承認待ち
        ]);

        $breakStarts = $request->break_start ?? [];
        $breakEnds   = $request->break_end ?? [];

        foreach ($breakStarts as $i => $start) {
            $end = $breakEnds[$i] ?? null;

            // 空欄はスキップ
            if (empty($start) && empty($end)) {
                continue;
            }

            $application->applicationBreaks()->create([
                'break_start' => $start ?: null,
                'break_end'   => $end ?: null,
            ]);
        }

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    ##勤怠更新申請処理
    public function update(ApplicationRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $user = Auth::user();

        $application = AttendanceApplication::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'remarks' => $request->remarks,
            'status' => 'pending', // 承認待ち
        ]);

        $breakStarts = $request->break_start ?? [];
        $breakEnds   = $request->break_end ?? [];

        foreach ($breakStarts as $i => $start) {
            $end = $breakEnds[$i] ?? null;

            if (empty($start) && empty($end)) {
                continue;
            }

            $application->applicationBreaks()->create([
                'break_start' => $start ?: null,
                'break_end'   => $end ?: null,
            ]);
        }

        $isPending = true;

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }
}
