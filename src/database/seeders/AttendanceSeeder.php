<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $today = Carbon::today();
        $yesterday = $today->copy()->subDay(); ##昨日まで
        $startDate = $today->copy()->subMonths(3); ##過去3ヶ月

        foreach ($users as $user) {
            $date = $startDate->copy();

            while ($date->lte($yesterday)) {

                // 土日を除外
                if ($date->isWeekend()) {
                    $date->addDay();
                    continue;
                }

                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'clock_in'  => $date->copy()->setTime(9, 0),
                        'clock_out' => $date->copy()->setTime(18, 0),
                        'status'    => '出勤',
                        'remarks'   => '',
                    ]
                );

                if (!$attendance) {
                    $date->addDay();
                    continue;
                }

                // 1〜3回ランダムで休憩を作成
                $breakCount = rand(1, 3);

                // 保存済みの breaktime を一旦削除（updateOrCreate だと増えるため）
                $attendance->breaks()->delete();

                for ($i = 0; $i < $breakCount; $i++) {
                    $breakStart = $date->copy()->setTime(12 + $i, rand(0, 30)); // 12:00〜14:30 のどこか
                    $breakEnd   = $breakStart->copy()->addMinutes(rand(15, 60)); // 15〜60分休憩

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => $breakStart,
                        'break_end'     => $breakEnd,
                    ]);
                }

                $date->addDay();
            }
        }
    }
}
