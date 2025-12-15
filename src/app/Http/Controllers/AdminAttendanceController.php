<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceApplication;
use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    ##勤怠一覧画面の表示
    public function list(Request $request)
    {
        $dateParam = $request->query('date');
        $currentDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        $users = User::all();
        $attendances = Attendance::with('breaks')
            ->where('date', $currentDate->format('Y-m-d'))
            ->get()
            ->keyBy('user_id');

        $data = [];
        foreach ($users as $user) {
            $attendance = $attendances->get($user->id);

            $breakTotal = null;
            $workTotal = null;

            if ($attendance) {
                // 休憩合計
                $breakMinutes = $attendance->breaks->reduce(function ($carry, $break) {
                    if ($break->break_start && $break->break_end) {
                        $start = $break->break_start->copy();
                        $end = $break->break_end->copy();
                        if ($end->lt($start)) $end->addDay();
                        return $carry + $start->diffInMinutes($end);
                    }
                    return $carry;
                }, 0);

                $breakTotal = $breakMinutes ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60) : null;

                // 勤務時間
                if ($attendance->clock_in && $attendance->clock_out) {
                    $clockIn = $attendance->clock_in->copy();
                    $clockOut = $attendance->clock_out->copy();
                    if ($clockOut->lt($clockIn)) $clockOut->addDay();
                    $workMinutes = max(0, $clockIn->diffInMinutes($clockOut) - $breakMinutes);
                    $workTotal = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
                }
            }

            $data[] = [
                'user' => $user,
                'attendance' => $attendance,
                'breakTotal' => $breakTotal,
                'workTotal' => $workTotal,
            ];
        }

        return view('admin.attendance.index', [
            'date' => $currentDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'data' => $data,
        ]);
    }

    ##勤怠詳細画面の表示
    public function detail(Request $request, $id = null)
    {
        $userId = $request->query('user_id') ?? auth()->id(); // リンクに user_id があれば使う
        $date   = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        if ($id) {
            $attendance = Attendance::with('user', 'breaks')->findOrFail($id);
        } else {
            $attendance = Attendance::with('user', 'breaks')
                ->firstOrCreate(
                    ['user_id' => $userId, 'date' => $date]
                );
        }

        $latestApplication = AttendanceApplication::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $isPending = $latestApplication ? true : false;

        if ($latestApplication) {
            $attendance->clock_in  = $latestApplication->clock_in
                ? Carbon::parse($latestApplication->clock_in)
                : null;

            $attendance->clock_out = $latestApplication->clock_out
                ? Carbon::parse($latestApplication->clock_out)
                : null;

            $attendance->remarks   = $latestApplication->remarks;

            $convertedBreaks = $latestApplication->applicationBreaks->map(function ($break) {
                $break->break_start = $break->break_start ? Carbon::parse($break->break_start) : null;
                $break->break_end   = $break->break_end   ? Carbon::parse($break->break_end)   : null;
                return $break;
            });

            $attendance->setRelation('breaks', $convertedBreaks);
        }

        return view('admin.attendance.show', compact('attendance', 'isPending'));
    }

    ##勤怠新規登録申請処理
    public function store(ApplicationRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $attendance->clock_in  = $request->clock_in;
        $attendance->clock_out = $request->clock_out;
        $attendance->remarks   = $request->remarks;
        $attendance->save();

        $attendance->breaks()->delete();

        $breakStarts = $request->break_start ?? [];
        $breakEnds   = $request->break_end ?? [];

        foreach ($breakStarts as $i => $start) {
            $end = $breakEnds[$i] ?? null;

            if (empty($start) && empty($end)) {
                continue;
            }

            $attendance->breaks()->create([
                'break_start' => $start ?: null,
                'break_end'   => $end ?: null,
            ]);
        }

        return redirect()->route('admin.attendance.detail', ['id' => $attendance->id]);
    }

    ##勤怠更新申請処理
    public function update(ApplicationRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $attendance->clock_in  = $request->clock_in;
        $attendance->clock_out = $request->clock_out;
        $attendance->remarks   = $request->remarks;
        $attendance->save();

        $attendance->breaks()->delete();

        $breakStarts = $request->break_start ?? [];
        $breakEnds   = $request->break_end ?? [];

        foreach ($breakStarts as $i => $start) {
            $end = $breakEnds[$i] ?? null;

            if (empty($start) && empty($end)) {
                continue;
            }

            $attendance->breaks()->create([
                'break_start' => $start ?: null,
                'break_end'   => $end ?: null,
            ]);
        }

        return redirect()->route('admin.attendance.detail', ['id' => $attendance->id]);
    }

    ##スタッフ別勤怠一覧画面の表示
    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);

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

                        if ($end->lt($start)) {
                            $end->addDay();
                        }

                        $diff = $start->diffInMinutes($end);

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

        return view('admin.staff.show', compact('user', 'monthLabel', 'prevMonth', 'nextMonth', 'dates'));
    }

    ##CSV出力
    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $monthParam = $request->query('month');
        $currentMonth = $monthParam ? Carbon::parse($monthParam . '-01') : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($att) => $att->date->format('Y-m-d'));

        $csv = "日付,出勤,退勤,休憩(合計),勤務時間\n";

        $date = $startOfMonth->copy();

        while ($date->lte($endOfMonth)) {
            $attendance = $attendances->get($date->format('Y-m-d'));

            $breakTotal = '';
            $workTotal = '';

            if ($attendance) {

                $breakMinutes = $attendance->breaks->reduce(function ($carry, $break) {
                    if ($break->break_start && $break->break_end) {
                        $start = $break->break_start->copy();
                        $end = $break->break_end->copy();
                        if ($end->lt($start)) $end->addDay();
                        return $carry + $start->diffInMinutes($end);
                    }
                    return $carry;
                }, 0);

                if ($breakMinutes) {
                    $breakTotal = sprintf("%d:%02d", floor($breakMinutes / 60), $breakMinutes % 60);
                }

                if ($attendance->clock_in && $attendance->clock_out) {
                    $clockIn = $attendance->clock_in->copy();
                    $clockOut = $attendance->clock_out->copy();
                    if ($clockOut->lt($clockIn)) $clockOut->addDay();

                    $workMinutes = max(0, $clockIn->diffInMinutes($clockOut) - $breakMinutes);
                    $workTotal = sprintf("%d:%02d", floor($workMinutes / 60), $workMinutes % 60);
                }
            }

            $csv .= implode(',', [
                $date->format('Y-m-d'),
                $attendance?->clock_in?->format('H:i') ?? '',
                $attendance?->clock_out?->format('H:i') ?? '',
                $breakTotal,
                $workTotal,
            ]) . "\n";

            $date->addDay();
        }

        $fileName = "勤怠_{$user->name}_{$currentMonth->format('Y.m')}.csv";

        $bom = "\xEF\xBB\xBF";

        return response($bom . $csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }
}
