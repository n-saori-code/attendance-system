{{-- resources/views/components/admin-header.blade.php --}}

<ul class="header__nav">

    <li class="header-nav__item"><a href="/admin/attendance/list">勤怠一覧</a></li>
    <li class="header-nav__item"><a href="/admin/staff/list">スタッフ一覧</a></li>
    <li class="header-nav__item"><a href="/admin/stamp_correction_request/list">申請一覧</a></li>
    <li class="header-nav__item">
        <form action="{{ route('admin.logout') }}" method="post">
            @csrf
            <button class="header-nav__button">ログアウト</button>
        </form>
    </li>

</ul>