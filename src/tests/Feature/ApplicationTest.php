<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceApplication;
use App\Models\Admin;
use Carbon\Carbon;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------
    // 勤怠詳細情報取得機能（一般ユーザー）
    // ------------------------------
    //勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_attendance_detail_page_displays_logged_in_user_name()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 15));

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);
        $response->assertSee('山田 太郎', false);
    }

    //勤怠詳細画面の「日付」が選択した日付になっている
    public function test_attendance_detail_page_displays_selected_date()
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

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee('2025年', false);

        $response->assertSee('3月10日', false);
    }

    //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_page_displays_correct_clock_in_and_out_time()
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

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee('09:00', false);

        $response->assertSee('18:00', false);
    }

    //「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_page_displays_correct_break_time()
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

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => '12:00',
            'break_end'     => '13:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);
        $response->assertSee('12:00', false);
        $response->assertSee('13:00', false);
    }

    // ------------------------------
    // 勤怠詳細情報修正機能（一般ユーザー）
    // ------------------------------
    //出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_is_displayed_when_clock_in_is_after_clock_out()
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

        $response = $this->post(
            route('attendance.update', ['id' => $attendance->id]),
            [
                'clock_in'  => '19:00',
                'clock_out' => '18:00',
                'remarks'   => 'テスト備考',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['clock_in']);

        $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        )->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    //休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_is_displayed_when_break_start_is_after_clock_out()
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

        $response = $this->post(
            route('attendance.update', ['id' => $attendance->id]),
            [
                'clock_in'          => '09:00',
                'clock_out'         => '18:00',
                'break_start'       => ['19:00'],
                'break_end'         => ['19:30'],
                'remarks'           => 'テスト備考',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['break_start.0']);

        $this->get(route('attendance.detail', ['id' => $attendance->id]))
            ->assertSee('休憩時間が不適切な値です');
    }

    //休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_is_displayed_when_break_end_is_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 10));

        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $response = $this->post(
            route('attendance.update', ['id' => $attendance->id]),
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_start' => [
                    '12:00',
                ],
                'break_end' => [
                    '19:00',
                ],
                'remarks' => 'テスト備考',
            ]
        );

        $response->assertStatus(302);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee(
            '休憩時間もしくは退勤時間が不適切な値です',
            false
        );
    }

    //備考欄が未入力の場合のエラーメッセージが表示される
    public function test_validation_error_is_displayed_when_remarks_is_empty()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 10));

        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $response = $this->post(
            route('attendance.update', ['id' => $attendance->id]),
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_start' => [
                    '12:00',
                ],
                'break_end' => [
                    '13:00',
                ],
                'remarks' => '',
            ]
        );

        $response->assertStatus(302);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );
        $response->assertStatus(200);

        $response->assertSee(
            '備考を記入してください',
            false
        );
    }

    //修正申請処理が実行される
    public function test_attendance_update_application_is_created_and_visible_for_admin()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 10));

        $user = User::factory()->create([]);

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $response = $this->post(
            route('attendance.update', ['id' => $attendance->id]),
            [
                'clock_in' => '10:00',
                'clock_out' => '19:00',
                'break_start' => ['13:00'],
                'break_end' => ['14:00'],
                'remarks' => '修正申請テスト',
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendance_applications', [
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
        ]);

        $application = AttendanceApplication::first();
        $this->assertNotNull($application);

        $admin = Admin::factory()->create([]);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertSee($user->name, false);
        $response->assertSee('修正申請テ', false);
    }

    //「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_pending_application_list_displays_all_logged_in_user_applications()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 10));

        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance1 = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-08',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $attendance2 = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-09',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        AttendanceApplication::create([
            'attendance_id' => $attendance1->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
            'remarks'       => '申請テスト1',
        ]);

        AttendanceApplication::create([
            'attendance_id' => $attendance2->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
            'remarks'       => '申請テスト2',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSee('申請テスト...', false);

        $response->assertSee('2025/03/08', false);
        $response->assertSee('2025/03/09', false);
    }

    //「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_approved_application_list_displays_all_admin_approved_applications()
    {
        Carbon::setTestNow(Carbon::create(2025, 3, 10));
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance1 = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-08',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $attendance2 = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-09',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $this->post(route('attendance.update', ['id' => $attendance1->id]), [
            'clock_in'    => '10:00',
            'clock_out'   => '19:00',
            'break_start' => ['13:00'],
            'break_end'   => ['14:00'],
            'remarks'     => '承認済み申請1',
        ]);

        $this->post(route('attendance.update', ['id' => $attendance2->id]), [
            'clock_in'    => '10:00',
            'clock_out'   => '19:00',
            'break_start' => ['13:00'],
            'break_end'   => ['14:00'],
            'remarks'     => '承認済み申請2',
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin);

        AttendanceApplication::query()->update([
            'status' => 'approved',
        ]);

        $this->actingAs($user);
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSee('承認済み', false);
        $response->assertSee($user->name, false);

        $response->assertSee('2025/03/08', false);
        $response->assertSee('2025/03/09', false);

        $response->assertSee('承認済み申...', false);
    }

    //各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_application_detail_link_navigates_to_attendance_detail_page()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-03-10',
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'status'    => '退勤済',
        ]);

        $application = AttendanceApplication::create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'clock_in'      => '10:00',
            'clock_out'     => '19:00',
            'remarks'       => '修正申請テスト',
            'status'        => 'pending',
        ]);

        $this->actingAs($user);
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSee(
            url('/attendance/detail/' . $attendance->id),
            false
        );

        $detailResponse = $this->get('/attendance/detail/' . $attendance->id);
        $detailResponse->assertStatus(200);
    }
}
