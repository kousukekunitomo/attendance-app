<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name', 'Attendance') }}</title>

  <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
  @stack('styles')
  @yield('css')
</head>

<body>
  @php
    $user = auth()->user();
    $isAdmin = (bool)($user?->is_admin);

    $isAttendanceIndex = request()->routeIs('attendance.index');

    // 一般ユーザーだけ「退勤後」のリンク出し分けを使う
    $isAfterWork = false;
    if (!$isAdmin && $isAttendanceIndex && $user) {
      $today = \Illuminate\Support\Carbon::today()->toDateString();
      $todayAttendance = \App\Models\Attendance::query()
        ->where('user_id', $user->id)
        ->whereDate('work_date', $today)
        ->first();
      $isAfterWork = $todayAttendance && !is_null($todayAttendance->clock_out_at);
    }

    $logoHref = $isAdmin
      ? route('admin.attendance.index')
      : route('attendance.index');
  @endphp

  <header class="ct-header">
    <div class="ct-header__inner">
      <a class="ct-header__logo" href="{{ $logoHref }}">
        <img src="{{ asset('images/coachteck-header-logo.png') }}" alt="COACHTECH">
      </a>

      <nav class="ct-header__nav">
        @auth
          @if($isAdmin)
            {{-- ✅ 見本どおり：CSV出力なし --}}
            <a href="{{ route('admin.attendance.index') }}">勤怠一覧</a>
            <a href="{{ route('admin.staff.index') }}">スタッフ一覧</a>
            <a href="{{ route('admin.stamp_correction_request.index') }}">申請一覧</a>
          @else
            @if($isAttendanceIndex && $isAfterWork)
              <a href="{{ route('attendance.list') }}">今月の出勤一覧</a>
              <a href="{{ route('stamp_correction_request.index') }}">申請一覧</a>
            @else
              <a href="{{ route('attendance.index') }}">勤怠</a>
              <a href="{{ route('attendance.list') }}">勤怠一覧</a>
              <a href="{{ route('stamp_correction_request.index') }}">申請</a>
            @endif
          @endif

          <form class="ct-header__logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">ログアウト</button>
          </form>
        @endauth
      </nav>
    </div>
  </header>

  <main class="ct-main">
    @yield('content')
  </main>

  @stack('scripts')
</body>
</html>
