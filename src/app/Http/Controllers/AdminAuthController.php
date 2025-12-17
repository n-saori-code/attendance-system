<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        if (! Auth::guard('admin')->attempt(
            $request->only('email', 'password')
        )) {
            throw ValidationException::withMessages([
                'password' => 'ログイン情報が登録されていません',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.attendance.list');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
