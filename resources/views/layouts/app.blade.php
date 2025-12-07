<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Flea Market App') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    @yield('css')
    @php $valCss = public_path('css/validation.css'); @endphp
<link rel="stylesheet"
      href="{{ asset('css/validation.css') }}@if(file_exists($valCss))?v={{ filemtime($valCss) }}@endif">
</head>
<body>
    <header class="header">
    <div class="logo">
         <a href="{{ route('items.index', ['tab' => 'recommend']) }}"
            aria-label="商品一覧（おすすめ）へ">
         <img src="{{ asset('logo.svg') }}" alt="COACHTECHロゴ">
         </a>
    </div>

    {{-- layouts/app.blade.php のヘッダー内 --}}
    <form class="search-form" action="{{ route('items.index') }}" method="GET">
  {{-- 今いるタブを保持（未指定時はおすすめ） --}}
  <input type="hidden" name="tab" value="{{ request('tab', 'recommend') }}">
  <input
    type="text"
    name="q"
    value="{{ request('q') }}"
    placeholder="なにをお探しですか？"
    maxlength="100"
    autocomplete="off"
  >
</form>



<nav class="nav-menu">
    @auth
        <form method="POST" action="{{ route('logout') }}" class="inline-form">
            @csrf
            <button type="submit" class="nav-button">ログアウト</button>
        </form>
        <a href="{{ route('mypage.index') }}" class="nav-link">マイページ</a>
        <a href="{{ route('items.create') }}" class="nav-button white">出品</a>
        
    @endauth

    @guest
        <a href="{{ route('login') }}" class="nav-link">ログイン</a>
        <a href="{{ route('login') }}" class="nav-link">マイページ</a>
        <a href="{{ route('login') }}" class="nav-button white">出品</a>
    @endguest
</nav>

</header>





    <main class="main">
        @yield('content')
    </main>

    @yield('js')
</body>
</html>
