@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css')}}">
@endsection

@section('link')
@include('components.admin-header')
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="contents-title">勤怠詳細</h1>

    <form action="{{ route('admin.application.approve', ['attendance_correct_request_id' => $application->id]) }}" method="POST">
        @csrf
        <div class="attendance__form-content">

            <div class="attendance__form-item">
                <p class="form__label">名前</p>
                <p class="form__text">{{ $application->user->name }}</p>
            </div>

            <div class="attendance__form-item">
                <p class="form__label">対象日</p>
                <div class="attendance__form-wrap">
                    <p class="form__text">{{ $application->attendance->date->format('Y年') }}</p>
                    <p class="form__text">{{ $application->attendance->date->format('n月j日') }}</p>
                </div>
            </div>

            <div class="attendance__form-item">
                <p class="form__label">出勤・退勤</p>
                <div class="attendance__form-wrap">
                    <div class="form__date-pending">{{ $application->clock_in?->format('H:i') ?? '' }}</div>
                    <span>〜</span>
                    <div class="form__date-pending">{{ $application->clock_out?->format('H:i') ?? '' }}</div>
                </div>
            </div>

            <!-- 休憩1 -->
            @php
            $break1 = $application->applicationBreaks->get(0);
            $start1 = $break1?->break_start?->format('H:i');
            $end1 = $break1?->break_end?->format('H:i');
            @endphp

            <div class="attendance__form-item">
                <p class="form__label">休憩1</p>
                <div class="attendance__form-wrap">
                    <div class="form__date-pending">{{ $start1 ?? '' }}</div>

                    @if($start1 && $end1)
                    <span>〜</span>
                    @endif

                    <div class="form__date-pending">{{ $end1 ?? '' }}</div>
                </div>
            </div>

            <!-- 休憩2 -->
            @php
            $break2 = $application->applicationBreaks->get(1);
            $start2 = $break2?->break_start?->format('H:i');
            $end2 = $break2?->break_end?->format('H:i');
            @endphp

            <div class="attendance__form-item">
                <p class="form__label">休憩2</p>
                <div class="attendance__form-wrap">
                    <div class="form__date-pending">{{ $start2 ?? '' }}</div>

                    @if($start2 && $end2)
                    <span>〜</span>
                    @endif

                    <div class="form__date-pending">{{ $end2 ?? '' }}</div>
                </div>
            </div>

            <!-- 休憩3以降 -->
            @foreach($application->applicationBreaks->slice(2) as $index => $break)
            <div class="attendance__form-item">
                <p class="form__label">休憩{{ $index + 3 }}</p>
                <div class="attendance__form-wrap">
                    <div class="form__date-pending">{{ $break->break_start?->format('H:i') ?? '' }}</div>
                    <span>〜</span>
                    <div class="form__date-pending">{{ $break->break_end?->format('H:i') ?? '' }}</div>
                </div>
            </div>
            @endforeach

            <!-- 備考 -->
            <div class="attendance__form-item">
                <p class="form__label">備考</p>
                <div class="form__textarea-pending">{{ $application->remarks }}</div>
            </div>
        </div>

        <div class="form__button">
            @if ($application->status === 'approved')
            <button type="button" class="form__button-submit disabled" disabled>
                承認済み
            </button>
            @else
            <button type="submit" class="form__button-submit">
                承認
            </button>
            @endif
        </div>
    </form>
</div>
@endsection