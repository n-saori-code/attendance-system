<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminStaffController extends Controller
{
    ##スタッフ一覧画面の表示
    public function list()
    {
        $users = User::all();
        return view('admin.staff.index', compact('users'));
    }
}
