@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css')}}">
@endsection

@section('link')
@include('components.header')
@endsection

@section('content')
<div class="attendance__content">
    <h1 class="cotents-title">勤怠詳細</h1>

    <form action="{{ $attendance->id
    ? route('attendance.update', ['id' => $attendance->id])
    : route('attendance.store') }}" method="POST" class="attendance__form">
        @csrf
        <input type="hidden" name="date" value="{{ $attendance->date?->format('Y-m-d') }}">
        <div class="attendance__form-content">

            <div class="attendance__form-item">
                <p class="form__label">名前</p>
                <p class="form__text">{{ Auth::user()->name }}</p>
            </div>

            <div class="attendance__form-item">
                <p class="form__label">日付</p>
                <div class="attendance__form-wrap">
                    <p class="form__text">{{ $attendance->date?->format('Y年') }}</p>
                    <p class="form__text">{{ $attendance->date?->format('n月j日') }}</p>
                </div>
            </div>

            <div class="attendance__form-item">
                <p class="form__label">出勤・退勤</p>
                <div class="attendance__form-inner">
                    <div class="attendance__form-wrap">
                        @if($isPending)
                        <div class="form__date-pending">{{ $attendance->clock_in?->format('H:i') ?? '' }}</div>
                        <span>〜</span>
                        <div class="form__date-pending">{{ $attendance->clock_out?->format('H:i') ?? '' }}</div>
                        @else
                        <input class="form__date" type="text" name="clock_in" value="{{ old('clock_in', $attendance->clock_in?->format('H:i')) }}" />
                        <span>〜</span>
                        <input class="form__date" type="text" name="clock_out" value="{{ old('clock_out', $attendance->clock_out?->format('H:i')) }}" />
                        @endif
                    </div>

                    <div class="form__error-wrap">
                        @error('clock_in') <div class="form__error">{{ $message }}</div> @enderror
                        @error('clock_out') <div class="form__error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- 休憩表示 -->
            @php
            $breaks = $attendance->breaks;
            $breakCount = $breaks->count();
            @endphp

            <!-- 休憩1 -->
            <div class="attendance__form-item">
                <p class="form__label">休憩1</p>
                <div class="attendance__form-inner">
                    <div class="attendance__form-wrap">
                        @if($isPending)
                        <div class="form__date-pending">{{ $breaks->get(0)?->break_start?->format('H:i') ?? '' }}</div>
                        <span>〜</span>
                        <div class="form__date-pending">{{ $breaks->get(0)?->break_end?->format('H:i') ?? '' }}</div>
                        @else
                        <input class="form__date" type="text" name="break_start[0]" value="{{ old('break_start.0', $breaks->get(0)?->break_start?->format('H:i')) }}">
                        <span>〜</span>
                        <input class="form__date" type="text" name="break_end[0]" value="{{ old('break_end.0', $breaks->get(0)?->break_end?->format('H:i')) }}">
                        @endif
                    </div>

                    <div class="form__error-wrap">
                        @error('break_start.0') <div class="form__error">{{ $message }}</div> @enderror
                        @error('break_end.0') <div class="form__error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- 休憩2 -->
            <div class="attendance__form-item">
                <p class="form__label">休憩2</p>
                <div class="attendance__form-inner">
                    <div class="attendance__form-wrap">
                        @if($isPending)
                        <div class="form__date-pending">{{ $breaks->get(1)?->break_start?->format('H:i') ?? '' }}</div>
                        <span>〜</span>
                        <div class="form__date-pending">{{ $breaks->get(1)?->break_end?->format('H:i') ?? '' }}</div>
                        @else
                        <input class="form__date" type="text" name="break_start[1]" value="{{ old('break_start.1', $breaks->get(1)?->break_start?->format('H:i')) }}">
                        <span>〜</span>
                        <input class="form__date" type="text" name="break_end[1]" value="{{ old('break_end.1', $breaks->get(1)?->break_end?->format('H:i')) }}">
                        @endif
                    </div>

                    <div class="form__error-wrap">
                        @error('break_start.1') <div class="form__error">{{ $message }}</div> @enderror
                        @error('break_end.1') <div class="form__error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- 休憩3以降 -->
            @if($breakCount >= 2)
            @foreach($breaks->slice(2)->values() as $i => $break)
            @php
            $fieldIndex = $i + 2; // 2,3,4...
            $labelNumber = $i + 3; // 3,4,5...
            @endphp

            <div class="attendance__form-item">
                <p class="form__label">休憩{{ $labelNumber }}</p>
                <div class="attendance__form-inner">
                    <div class="attendance__form-wrap">
                        @if($isPending)
                        <div class="form__date-pending">{{ $break->break_start?->format('H:i') ?? '' }}</div>
                        <span>〜</span>
                        <div class="form__date-pending">{{ $break->break_end?->format('H:i') ?? '' }}</div>
                        @else
                        <input class="form__date" type="text" name="break_start[{{ $fieldIndex }}]" value="{{ old("break_start.$fieldIndex", $break->break_start?->format('H:i')) }}">
                        <span>〜</span>
                        <input class="form__date" type="text" name="break_end[{{ $fieldIndex }}]" value="{{ old("break_end.$fieldIndex", $break->break_end?->format('H:i')) }}">
                        @endif
                    </div>

                    <div class="form__error-wrap">
                        @error("break_start.$fieldIndex") <div class="form__error">{{ $message }}</div> @enderror
                        @error("break_end.$fieldIndex") <div class="form__error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            @endforeach
            @endif

            <!-- 新規追加行（常に1行だけ追加） -->
            @if($breakCount >= 2)
            @php
            $newIndex = $breakCount;
            $newLabel = $newIndex + 1;
            @endphp

            <div class="attendance__form-item">
                <p class="form__label">休憩{{ $newLabel }}</p>
                <div class="attendance__form-inner">
                    <div class="attendance__form-wrap">
                        @if($isPending)
                        <div class="form__date-pending"></div>
                        <span>〜</span>
                        <div class="form__date-pending"></div>
                        @else
                        <input class="form__date" type="text" name="break_start[{{ $newIndex }}]" value="{{ old("break_start.$newIndex") }}">
                        <span>〜</span>
                        <input class="form__date" type="text" name="break_end[{{ $newIndex }}]" value="{{ old("break_end.$newIndex") }}">
                        @endif
                    </div>

                    <div class="form__error-wrap">
                        @error("break_start.$newIndex") <div class="form__error">{{ $message }}</div> @enderror
                        @error("break_end.$newIndex") <div class="form__error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            @endif

            {{-- 備考 --}}
            <div class="attendance__form-item">
                <p class="form__label">備考</p>
                <div class="form__textarea-wrap">
                    @if($isPending)
                    <div class="form__textarea-pending">{{ $attendance->remarks ?? '' }}</div>
                    @else
                    <textarea class="form__textarea" name="remarks">{{ old('remarks', $attendance->remarks ?? '') }}</textarea>
                    @endif

                    <div class="form__error-wrap">
                        @error('remarks') <div class="form__error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

        </div>

        <div class="form__button">
            @if($isPending)
            <p class="form__button-pending">*承認待ちのため修正はできません。</p>
            @else
            <button type="submit" class="form__button-submit">修正</button>
            @endif
        </div>

    </form>
</div>
@endsection