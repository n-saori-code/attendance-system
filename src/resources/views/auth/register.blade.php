@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/form.css')}}">
@endsection

@section('content')
<div class="form-content__inner">
    <h1 class="register__form__title">会員登録</h1>
    <form class="form" action="/register" method="post" novalidate>
        @csrf
        <div class="register__form__group">
            <p class="form__group-title">名前</p>
            <input class="register__input" type="text" name="name" value="{{ old('name') }}" />
            <div class="form__error">
                @error('name')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="register__form__group">
            <p class="form__group-title">メールアドレス</p>
            <input class="register__input" type="email" name="email" value="{{ old('email') }}" />
            <div class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="register__form__group">
            <p class="form__group-title">パスワード</p>
            <input class="register__input" type="password" name="password" />
            <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="register__form__group">
            <p class="form__group-title">パスワード確認</p>
            <input class="register__input" type="password" name="password_confirmation" />
        </div>

        <button class="form__button" type="submit">登録する</button>
        <a class="link" href="/login">ログインはこちら</a>
    </form>
</div>

@endsection