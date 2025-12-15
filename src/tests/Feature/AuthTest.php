<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;


class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function validRegistrationData(array $overrides = [])
    {
        return array_merge([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    // ------------------------------
    // 認証機能（一般ユーザー）
    // ------------------------------
    //名前が未入力の場合、バリデーションメッセージが表示される
    public function test_name_is_required()
    {
        $response = $this->post(
            '/register',
            $this->validRegistrationData(['name' => ''])
        );

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
    }

    //メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_email_is_required()
    {
        $response = $this->post(
            '/register',
            $this->validRegistrationData(['email' => ''])
        );

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される
    public function test_password_must_be_min_8_chars()
    {
        $response = $this->post('/register', $this->validRegistrationData(['password' => 'pass123', 'password_confirmation' => 'pass123']));

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    // パスワードが一致しない場合、バリデーションメッセージが表示される
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', $this->validRegistrationData(['password_confirmation' => 'different123']));

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません'
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_password_is_required()
    {
        $response = $this->post('/register', $this->validRegistrationData(['password' => '', 'password_confirmation' => '']));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    // フォームに内容が入力されていた場合、データが正常に保存される
    public function test_user_is_created_when_valid_data_is_provided()
    {
        $response = $this->post(
            '/register',
            $this->validRegistrationData()
        );

        $this->assertDatabaseHas('users', [
            'name'  => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/email/verify');
    }

    // ------------------------------
    // 認証メール
    // ------------------------------
    // 会員登録後、認証メールが送信される
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $this->post('/register', $this->validRegistrationData());
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }


    // メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_email_verification_page_displays_correctly()
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $response = $this->get('/email/verify');
        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');
        $response->assertSee(config('app.url'));
    }

    // メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
    public function test_user_can_verify_email_and_redirect()
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('attendance.index'));
    }

    // ------------------------------
    // ログイン認証機能（一般ユーザー）
    // ------------------------------
    // ログインリクエストを送信（メールアドレス未入力）
    public function test_login_fails_when_email_is_missing()
    {
        $response = $this->post('/login', ['email' => '', 'password' => 'password123']);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_login_fails_when_password_is_missing()
    {
        $response = $this->post('/login', ['email' => 'test@example.com', 'password' => '']);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_login_fails_with_unregistered_email()
    {
        $response = $this->post('/login', ['email' => 'notfound@example.com', 'password' => 'wrongpassword']);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }

    // 正しい情報が入力された場合、ログイン処理が実行される
    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('attendance.index'));
    }

    // ------------------------------
    // ログイン認証機能（管理者）
    // ------------------------------
    // ログインリクエストを送信（メールアドレス未入力）
    public function test_adminlogin_fails_when_email_is_missing()
    {
        $response = $this->post('/admin/login', ['email' => '', 'password' => 'password123']);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_adminlogin_fails_when_password_is_missing()
    {
        $response = $this->post('/admin/login', ['email' => 'test@example.com', 'password' => '']);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_adminlogin_fails_with_unregistered_email()
    {
        $response = $this->post('/admin/login', ['email' => 'notfound@example.com', 'password' => 'wrongpassword']);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }

    // 正しい情報が入力された場合、ログイン処理が実行される
    public function test_adminuser_can_login_with_correct_credentials()
    {
        $admin = Admin::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($admin, 'admin');

        $response->assertRedirect(route('admin.attendance.list'));
    }
}
