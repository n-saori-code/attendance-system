<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceApplication;
use Carbon\Carbon;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------
    // 勤怠一覧情報取得機能（管理者）
    // ------------------------------
    //その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_can_view_all_users_attendance_for_today()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 10));

        $admin = Admin::factory()->create();

        $user1 = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤 花子',
        ]);

        Attendance::create([
            'user_id'   => $user1->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        Attendance::create([
            'user_id'   => $user2->id,
            'date'      => '2025-03-10',
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('山田 太郎', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);

        $response->assertSee('佐藤 花子', false);
        $response->assertSee('10:00', false);
        $response->assertSee('19:00', false);
    }

    //遷移した際に現在の日付が表示される
    public function test_admin_attendance_list_shows_today_date()
    {
        Carbon::setTestNow('2025-03-10');

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('2025/03/10');
    }

    //「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_admin_can_view_previous_day_attendance()
    {
        Carbon::setTestNow('2025-03-10');

        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-09',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=2025-03-09');
        $response->assertStatus(200);

        $response->assertSee('2025/03/09');

        $response->assertSee('テストユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_admin_can_view_next_day_attendance()
    {
        Carbon::setTestNow('2025-03-10');

        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-11',
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=2025-03-11');
        $response->assertStatus(200);

        $response->assertSee('2025/03/11');

        $response->assertSee('テストユーザー');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    // ------------------------------
    // 勤怠詳細情報取得・修正機能（管理者）
    // ------------------------------
    //勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_can_view_correct_attendance_detail()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(
            route('admin.attendance.detail', ['id' => $attendance->id])
        );
        $response->assertStatus(200);

        $response->assertSee('勤怠詳細');

        $response->assertSee('テスト太郎');

        $response->assertSee('2025年');
        $response->assertSee('3月10日');
    }

    //出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_is_displayed_when_clock_in_is_after_clock_out()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->post(
            route('admin.attendance.update', ['id' => $attendance->id]),
            [
                'clock_in'  => '19:00',
                'clock_out' => '18:00',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'clock_in',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_is_displayed_when_break_start_is_after_clock_out()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->post(
            route('admin.attendance.update', ['id' => $attendance->id]),
            [
                'clock_in'          => '09:00',
                'clock_out'         => '18:00',
                'break_start'       => ['19:00'],
                'break_end'         => ['19:30'],
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'break_start.0' => '休憩時間が不適切な値です',
        ]);
    }

    //休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_is_displayed_when_break_end_is_after_clock_out()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->post(
            route('admin.attendance.update', ['id' => $attendance->id]),
            [
                'clock_in'    => '09:00',
                'clock_out'   => '18:00',
                'break_start' => ['17:00'],
                'break_end'   => ['18:30'],
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //備考欄が未入力の場合のエラーメッセージが表示される
    public function test_error_is_displayed_when_remarks_is_empty()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->post(
            route('admin.attendance.update', ['id' => $attendance->id]),
            [
                'clock_in'    => '09:00',
                'clock_out'   => '18:00',
                'break_start' => ['12:00'],
                'break_end'   => ['13:00'],
                'remarks'     => '',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    // ------------------------------
    // ユーザー情報取得機能（管理者）
    // ------------------------------
    //管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_view_all_users_name_and_email()
    {
        $admin = Admin::factory()->create();

        $users = User::factory()->count(3)->create([
            'name'  => 'テストユーザー',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    //ユーザーの勤怠情報が正しく表示される
    public function test_admin_can_view_selected_users_attendance_list()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => Carbon::today(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(
            route('admin.attendance.staff', ['id' => $user->id])
        );

        $response->assertStatus(200);

        $response->assertSee($user->name);

        $weekJa = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'];
        $today = Carbon::today();
        $displayDate = $today->format('m/d') . '(' . $weekJa[$today->format('D')] . ')';

        $response->assertSee($displayDate);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //「前月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_can_view_previous_month_attendance()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $lastMonth = $today->copy()->subMonth();

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $lastMonth->copy()->startOfMonth(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $today,
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(
            route('admin.attendance.staff', ['id' => $user->id, 'month' => $lastMonth->format('Y-m')])
        );
        $response->assertStatus(200);

        $weekJa = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'];
        $displayDate = $lastMonth->copy()->startOfMonth()->format('m/d') . '(' . $weekJa[$lastMonth->copy()->startOfMonth()->format('D')] . ')';

        $response->assertSee($displayDate);
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $notDisplayDate = $today->format('m/d') . '(' . $weekJa[$today->format('D')] . ')';
        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
        $response->assertDontSee($notDisplayDate);
    }

    //「翌月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_can_view_next_month_attendance()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $nextMonth = $today->copy()->addMonth();

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $nextMonth->copy()->startOfMonth(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $today,
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(
            route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth->format('Y-m')])
        );

        $response->assertStatus(200);

        $weekJa = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'];
        $displayDate = $nextMonth->copy()->startOfMonth()->format('m/d') . '(' . $weekJa[$nextMonth->copy()->startOfMonth()->format('D')] . ')';

        $response->assertSee($displayDate);
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $notDisplayDate = $today->format('m/d') . '(' . $weekJa[$today->format('D')] . ')';
        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
        $response->assertDontSee($notDisplayDate);
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_admin_can_navigate_to_attendance_detail_from_list()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['name' => 'テストユーザー']);

        $fixedDate = Carbon::create(2025, 12, 15);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $fixedDate,
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attendance.staff', ['id' => $user->id]));
        $response->assertStatus(200);

        $detailUrl = route('admin.attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);

        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);

        $detailResponse->assertSee('2025年', false);
        $detailResponse->assertSee('12月15日', false);

        $detailResponse->assertSee('テストユーザー', false);
        $detailResponse->assertSee('09:00', false);
        $detailResponse->assertSee('18:00', false);
    }

    // ------------------------------
    // 勤怠情報修正機能（管理者）
    // ------------------------------
    //承認待ちの修正申請が全て表示されている
    public function test_admin_can_see_all_pending_attendance_corrections()
    {
        $admin = Admin::factory()->create();

        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        $attendance1 = Attendance::create([
            'user_id'   => $user1->id,
            'date'      => '2025-12-15',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $attendance2 = Attendance::create([
            'user_id'   => $user2->id,
            'date'      => '2025-12-16',
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'status'    => '退勤済',
        ]);

        $pendingRequest1 = AttendanceApplication::create([
            'user_id'       => $user1->id,
            'attendance_id' => $attendance1->id,
            'date'          => '2025-12-15',
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'status'        => 'pending',
            'remarks'       => '修正1',
        ]);

        $pendingRequest2 = AttendanceApplication::create([
            'user_id'       => $user2->id,
            'attendance_id' => $attendance2->id,
            'date'          => '2025-12-16',
            'clock_in'      => '10:00',
            'clock_out'     => '19:00',
            'status'        => 'pending',
            'remarks'       => '修正2',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.application.list'));

        $response->assertStatus(200);

        $response->assertSeeText('ユーザー1');
        $response->assertSeeText('2025/12/15');
        $response->assertSeeText('修正1');

        $response->assertSeeText('ユーザー2');
        $response->assertSeeText('2025/12/16');
        $response->assertSeeText('修正2');
    }

    //承認済みの修正申請が全て表示されている
    public function test_admin_can_see_all_approved_attendance_corrections()
    {
        $admin = Admin::factory()->create();

        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        $attendance1 = Attendance::create([
            'user_id'   => $user1->id,
            'date'      => '2025-12-15',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $attendance2 = Attendance::create([
            'user_id'   => $user2->id,
            'date'      => '2025-12-16',
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
            'status'    => '退勤済',
        ]);

        $approvedRequest1 = AttendanceApplication::create([
            'user_id'       => $user1->id,
            'attendance_id' => $attendance1->id,
            'date'          => '2025-12-15',
            'clock_in'      => '09:00',
            'clock_out'     => '18:00',
            'status'        => 'approved',
            'remarks'       => '修正済1',
        ]);

        $approvedRequest2 = AttendanceApplication::create([
            'user_id'       => $user2->id,
            'attendance_id' => $attendance2->id,
            'date'          => '2025-12-16',
            'clock_in'      => '10:00',
            'clock_out'     => '19:00',
            'status'        => 'approved',
            'remarks'       => '修正済2',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.application.list'));
        $response->assertStatus(200);

        $response->assertSeeText('ユーザー1');
        $response->assertSeeText('2025/12/15');
        $response->assertSeeText('修正済1');

        $response->assertSeeText('ユーザー2');
        $response->assertSeeText('2025/12/16');
        $response->assertSeeText('修正済2');
    }

    //修正申請の詳細内容が正しく表示されている
    public function test_admin_can_view_attendance_correction_details()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create(['name' => 'テストユーザー']);

        $fixedDate = Carbon::create(2025, 12, 16);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $fixedDate,
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
            'remarks'   => '通常勤務',
        ]);

        $application = AttendanceApplication::create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'date'          => $attendance->date,
            'clock_in'      => '09:30',
            'clock_out'     => '18:30',
            'status'        => 'pending',
            'remarks'       => '勤務時間修正',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.application.detail', ['id' => $application->id]));
        $response->assertStatus(200);

        $response->assertSee('テストユーザー', false);
        $response->assertSee('2025年', false);
        $response->assertSee('12月16日', false);
        $response->assertSee('09:30', false);
        $response->assertSee('18:30', false);
        $response->assertSee('勤務時間修正', false);

        $response->assertSee('承認', false);
    }

    //修正申請の承認処理が正しく行われる
    public function test_admin_can_approve_attendance_correction()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $fixedDate = Carbon::create(2025, 12, 16);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $fixedDate,
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
            'remarks'   => '通常勤務',
        ]);

        $application = AttendanceApplication::create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'date'          => $attendance->date,
            'clock_in'      => '09:30',
            'clock_out'     => '18:30',
            'status'        => 'pending',
            'remarks'       => '勤務時間修正',
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->post(route('admin.application.approve', [
            'attendance_correct_request_id' => $application->id
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.application.detail', ['id' => $application->id]));

        $this->assertDatabaseHas('attendance_applications', [
            'id'     => $application->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => $fixedDate->copy()->setTimeFromTimeString('09:30:00'),
            'clock_out' => $fixedDate->copy()->setTimeFromTimeString('18:30:00'),
        ]);
    }
}
