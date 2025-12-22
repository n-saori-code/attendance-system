@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('link')
@include('components.admin-header')
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="contents-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>

    <div class="attendance__calendar">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="calendar__btn prev">前日</a>
        <p class="calendar__current">{{ $date->format('Y/m/d') }}</p>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="calendar__btn next">翌日</a>
    </div>

    <table class="attendance__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
            <tr>
                <td>{{ $item['user']->name }}</td>
                <td>{{ $item['attendance']?->clock_in?->format('H:i') ?? '' }}</td>
                <td>{{ $item['attendance']?->clock_out?->format('H:i') ?? '' }}</td>
                <td>{{ $item['breakTotal'] ?? '' }}</td>
                <td>{{ $item['workTotal'] ?? '' }}</td>
                <td>
                    <a href="{{ $item['attendance']
        ? route('admin.attendance.detail', ['id' => $item['attendance']->id])
        : route('admin.attendance.detail', ['date' => $date->format('Y-m-d'), 'user_id' => $item['user']->id]) }}"
                        class="detail__link">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection

@section('script')

@endsection