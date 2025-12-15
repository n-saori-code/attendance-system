@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/create.css')}}">
@endsection

@section('link')
@include('components.header')
@endsection

@section('content')

@php
$status = $attendance?->status ?? '勤務外';
$day = ['日','月','火','水','木','金','土'][$today->dayOfWeek];
@endphp

<form class="attendance__content" action="{{ route('attendance.clock') }}" method="POST">
    @csrf
    <p class="attendance__status">{{ $status }}</p>
    <p class="attendance__date">{{ $today->format('Y年m月d日') }} ({{ $day }})</p>
    <p class="attendance__time" id="currentTime">-- : --</p>

    <input type="hidden" name="clock_time" id="clockTime">

    <div class="button__item">
        @if ($status === '勤務外')
        <button type="submit" name="action" value="clock_in" class="attendance__button">出勤</button>

        @elseif ($status === '出勤中')
        <button type="submit" name="action" value="clock_out" class="attendance__button">退勤</button>
        <button type="submit" name="action" value="break_start" class="attendance__break-button">休憩入</button>

        @elseif ($status === '休憩中')
        <button type="submit" name="action" value="break_end" class="attendance__break-button">休憩戻</button>

        @elseif ($status === '退勤済')
        <p class="attendance__clock-out-button">お疲れ様でした。</p>
        @endif
    </div>
</form>
@endsection

@section('script')
<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        const timeStr = `${hours}:${minutes}`;
        document.getElementById('currentTime').textContent = timeStr;

        document.getElementById('clockTime').value = now.toISOString();
    }

    setInterval(updateTime, 1000);
    updateTime();
</script>
@endsection