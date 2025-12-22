@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('link')
@include('components.header')
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="contents-title">勤怠一覧</h1>

    <div class="attendance__calendar">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="calendar__btn prev">前月</a>
        <p class="calendar__current">{{ $monthLabel }}</p>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="calendar__btn next">翌月</a>
    </div>

    <table class="attendance__table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dates as $item)
            @php
            $date = $item['date'];
            $attendance = $item['attendance'];
            @endphp
            <tr>
                <td>{{ $date->format('m/d') }}({{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }})</td>
                <td>{{ $attendance?->clock_in?->format('H:i') ?? '' }}</td>
                <td>{{ $attendance?->clock_out?->format('H:i') ?? '' }}</td>
                <td>{{ $item['breakTotal'] ?? '' }}</td>
                <td>{{ $item['workTotal'] ?? '' }}</td>
                <td>
                    <a href="{{ $attendance
                    ? route('attendance.detail', ['id' => $attendance->id])
                    : route('attendance.detail', ['date' => $date->format('Y-m-d')]) }}"
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