@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/form.css')}}">
@endsection

@section('content')
<div class="form-content__inner">
    <h1 class="form__title">管理者ログイン</h1>
    <form class="form" action="{{ route('admin.login') }}" method="post" novalidate>
        @csrf
        <div class="form__group">
            <p class="form__group-title">メールアドレス</p>
            <input class="input" type="email" name="email" value="{{ old('email') }}" />
            <div class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <p class="form__group-title">パスワード</p>
            <input class="input" type="password" name="password" />
            <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>

        <button class="form__button" type="submit">管理者ログインする</button>
    </form>
</div>

@endsection