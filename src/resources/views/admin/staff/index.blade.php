@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('link')
@include('components.admin-header')
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="contents-title">スタッフ一覧</h1>

    <table class="attendance__table staff__wrap">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <td>{{ $user['name'] }}</td>
                <td>{{ $user['email'] }}</td>
                <td><a href="{{ route('admin.attendance.staff', ['id' => $user['id']]) }}" class="detail__link">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection

@section('script')

@endsection