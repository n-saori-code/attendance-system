@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('link')
@include('components.header')
@endsection

@section('content')
@php
use Illuminate\Support\Str;
@endphp

<div class="attendance__content">
    <h1 class="contents-title">申請一覧</h1>

    <input type="radio" name="tab" id="tab1" checked hidden>
    <input type="radio" name="tab" id="tab2" hidden>

    <ul class="applications__tab">
        <li class="applications__list"><label for="tab1">承認待ち</label></li>
        <li class="applications__list"><label for="tab2">承認済み</label></li>
    </ul>

    <!-- 承認待ち -->
    <div class="tab_content" id="content1">
        <table class="attendance__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $app)
                <tr>
                    <td>
                        @if($app->status === 'pending')
                        承認待ち
                        @elseif($app->status === 'approved')
                        承認済み
                        @endif
                    </td>
                    <td>{{ $app->user->name }}</td>
                    <td class="date">{{ $app->attendance->date->format('Y/m/d') }}</td>
                    <td>{{ Str::limit($app->remarks, 10) }}</td>
                    <td class="date">{{ $app->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('attendance.detail', ['id' => $app->attendance->id]) }}" class="detail__link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 承認済み -->
    <div class="tab_content" id="content2">
        <table class="attendance__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approved as $app)
                <tr>
                    <td>
                        @if($app->status === 'pending')
                        承認待ち
                        @elseif($app->status === 'approved')
                        承認済み
                        @endif
                    </td>
                    <td>{{ $app->user->name }}</td>
                    <td class="date">{{ $app->attendance->date->format('Y/m/d') }}</td>
                    <td>{{ Str::limit($app->remarks, 10) }}</td>
                    <td class="date">{{ $app->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('attendance.detail', ['id' => $app->attendance->id]) }}" class="detail__link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection