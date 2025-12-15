<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


class AuthController extends Controller
{
    ##会員登録処理
    public function register(RegisterRequest $request)
    {
        $form = $request->only(['name', 'email', 'password']);
        $form['password'] = Hash::make($form['password']);
        $user = User::create($form);
        event(new Registered($user));
        Auth::login($user);
        return redirect()->route('verification.notice');
    }

    ##ログイン認証
    public function login(LoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            session(['from_login' => true]);
            return redirect()->route('attendance.index');
        }
    }

    ##ログアウト
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    ## メール認証ページ
    public function notice(Request $request)
    {
        return view('auth.verify-email');
    }

    ## メール内リンククリック時（認証完了）
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('attendance.index');
    }

    ## 認証メール再送
    public function resend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back();
    }
}
