<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------
    // 日時取得機能
    // ------------------------------
    //現在の日時情報がUIと同じ形式で出力されている
    public function test_today_date_is_displayed_correctly()
    {
        $today = Carbon::create(2025, 3, 1);
        Carbon::setTestNow($today);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2025年03月01日');

        $response->assertSee('(土)');

        $response->assertSee('name="clock_time"', false);
    }

    // ------------------------------
    // ステータス確認機能
    // ------------------------------
    //勤務外の場合、勤怠ステータスが正しく表示される
    public function test_status_is_displayed_as_off_duty_when_user_has_no_attendance()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    //出勤中の場合、勤怠ステータスが正しく表示される
    public function test_status_is_displayed_as_working_when_user_is_clocked_in()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date'    => Carbon::today(),
            'status'  => '出勤中',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    //休憩中の場合、勤怠ステータスが正しく表示される
    public function test_status_is_displayed_as_on_break_when_user_is_on_break()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date'    => now()->toDateString(),
            'status'  => '休憩中',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    //退勤済の場合、勤怠ステータスが正しく表示される
    public function test_status_is_displayed_as_clocked_out_when_user_is_clocked_out()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date'    => now()->toDateString(),
            'status'  => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    // ------------------------------
    // 出勤機能
    // ------------------------------
    //出勤ボタンが正しく機能する
    public function test_user_can_clock_in_when_status_is_off_duty()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤');

        $response = $this->post(route('attendance.clock'), [
            'action'     => 'clock_in',
            'clock_time' => now()->toISOString(),
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date'    => now()->toDateString(),
            'status'  => '出勤中',
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
    }

    //出勤は一日一回のみできる
    public function test_clock_in_button_is_not_displayed_when_user_already_clocked_out_today()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date'    => now()->toDateString(),
            'status'  => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        $response->assertDontSee('出勤');

        $response->assertSee('退勤済');
    }

    //出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_is_displayed_on_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 9, 0, 0));

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('attendance.clock'), [
            'action'     => 'clock_in',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $expectedTime = now()->format('H:i');

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        $response->assertSee($expectedTime);
    }

    // ------------------------------
    // 休憩機能
    // ------------------------------
    //休憩ボタンが正しく機能する
    public function test_break_start_button_works_correctly()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHour(),
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $this->post(route('attendance.clock'), [
            'action'     => 'break_start',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => '休憩中',
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');
    }

    //休憩は一日に何回でもできる
    public function test_break_can_be_taken_multiple_times_in_a_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHour(),
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $this->post(route('attendance.clock'), [
            'action'     => 'break_start',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => '休憩中',
        ]);

        Carbon::setTestNow(now()->addMinutes(10));

        $this->post(route('attendance.clock'), [
            'action'     => 'break_end',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => '出勤中',
        ]);

        $response = $this->get(route('attendance.index'));

        $response->assertSee('休憩入');
    }

    //休憩戻ボタンが正しく機能する
    public function test_break_end_button_works_correctly()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 11, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHour(),
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.clock'), [
            'action'     => 'break_start',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        Carbon::setTestNow(now()->addMinutes(15));

        $this->post(route('attendance.clock'), [
            'action'     => 'break_end',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => '出勤中',
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
    }

    //休憩戻は一日に何回でもできる
    public function test_break_end_can_be_done_multiple_times_per_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHour(),
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.clock'), [
            'action'     => 'break_start',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        Carbon::setTestNow(now()->addMinutes(10));

        $this->post(route('attendance.clock'), [
            'action'     => 'break_end',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        Carbon::setTestNow(now()->addMinutes(20));

        $this->post(route('attendance.clock'), [
            'action'     => 'break_start',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }

    //休憩時刻が勤怠一覧画面で確認できる
    public function test_break_time_is_displayed_on_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 9, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHour(),
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.clock'), [
            'action'     => 'break_start',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        Carbon::setTestNow(now()->addMinutes(30));

        $this->post(route('attendance.clock'), [
            'action'     => 'break_end',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);

        $response->assertSee('0:30');
    }

    // ------------------------------
    // 退勤機能
    // ------------------------------
    //退勤ボタンが正しく機能する
    public function test_clock_out_button_works_correctly()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 10, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHours(2),
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $this->post(route('attendance.clock'), [
            'action'     => 'clock_out',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id'     => $attendance->id,
            'status' => '退勤済',
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertSee('退勤済');
    }

    //退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_is_displayed_on_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 18, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => now()->toDateString(),
            'clock_in' => now()->subHours(9), // 09:00 出勤
            'status'   => '出勤中',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.clock'), [
            'action'     => 'clock_out',
            'clock_time' => now()->toISOString(),
        ])->assertRedirect();

        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('18:00');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date'    => now()->toDateString(),
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->first();

        $this->assertNotNull($attendance->clock_out);
    }

    // ------------------------------
    // 勤怠一覧情報取得機能（一般ユーザー）
    // ------------------------------
    //自分が行った勤怠情報が全て表示されている
    public function test_attendance_list_displays_all_records_of_authenticated_user()
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 10, 18, 0, 0));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 自分の勤怠データ
        Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-01-09',
            'clock_in'  => Carbon::create(2025, 1, 9, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 1, 9, 18, 0, 0),
            'status'    => '退勤済',
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-01-10',
            'clock_in'  => Carbon::create(2025, 1, 10, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 1, 10, 18, 0, 0),
            'status'    => '退勤済',
        ]);

        Attendance::create([
            'user_id'   => $otherUser->id,
            'date'      => '2025-01-10',
            'clock_in'  => Carbon::create(2025, 1, 10, 10, 0, 0),
            'clock_out' => Carbon::create(2025, 1, 10, 19, 0, 0),
            'status'    => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        $response->assertSee('01/09(木)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('01/10(金)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }

    //勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_attendance_list_displays_current_month()
    {
        $currentDate = Carbon::create(2025, 3, 15);
        Carbon::setTestNow($currentDate);

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);

        $expectedMonth = $currentDate->format('Y/m');
        $response->assertSee($expectedMonth);
    }

    //「前月」を押下した時に表示月の前月の情報が表示される
    public function test_attendance_list_displays_previous_month_when_prev_button_clicked()
    {
        $currentDate = Carbon::create(2025, 3, 15);
        Carbon::setTestNow($currentDate);

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => '2025-02-10',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status'   => '退勤済',
        ]);

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => '2025-03-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status'   => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=2025-02');

        $response->assertStatus(200);

        $response->assertSee('2025/02');

        $response->assertSee('02/10(月)', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);

        $response->assertDontSee('03/10(月)');
        $response->assertDontSee('10:00', false);
        $response->assertDontSee('19:00', false);
    }

    //「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_attendance_list_displays_previous_month_when_next_button_clicked()
    {
        $currentDate = Carbon::create(2025, 3, 15);
        Carbon::setTestNow($currentDate);

        $user = User::factory()->create();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => '2025-03-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status'   => '退勤済',
        ]);

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => '2025-04-10',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status'   => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=2025-04');

        $response->assertStatus(200);

        $response->assertSee('2025/04');

        $response->assertSee('04/10(木)', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);

        $response->assertDontSee('03/10(月)');
        $response->assertDontSee('10:00', false);
        $response->assertDontSee('19:00', false);
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_attendance_detail_page_is_displayed_when_detail_button_is_clicked()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 15));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($user);

        $listResponse = $this->get(route('attendance.list'));
        $listResponse->assertStatus(200);

        $listResponse->assertSee(
            route('attendance.detail', ['id' => $attendance->id]),
            false
        );

        $detailResponse = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $detailResponse->assertStatus(200);

        $detailResponse->assertSee('2025年', false);
        $detailResponse->assertSee('3月10日', false);
    }
}
