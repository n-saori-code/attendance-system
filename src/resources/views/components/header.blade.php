{{-- resources/views/components/header.blade.php --}}

<ul class="header__nav">

    <li class="header-nav__item"><a href="/attendance">勤怠</a></li>
    <li class="header-nav__item"><a href="/attendance/list">勤怠一覧</a></li>
    <li class="header-nav__item"><a href="/stamp_correction_request/list">申請</a></li>
    <li class="header-nav__item">
        <form action="/logout" method="post">
            @csrf
            <button class="header-nav__button">ログアウト</button>
        </form>
    </li>

</ul>