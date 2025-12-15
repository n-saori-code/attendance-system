@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css')}}">
@endsection

@section('content')
<div class="verify-container">
    <p>
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <a href="http://localhost:8025" class="btn">認証はこちらから</a>

    <a href="{{ route('verification.send') }}" class="link-button" onclick="event.preventDefault(); document.getElementById('resend-form').submit();">
        認証メールを再送する
    </a>

    <form id="resend-form" method="POST" action="{{ route('verification.send') }}" style="display: none;">
        @csrf
    </form>

</div>

@endsection