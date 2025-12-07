<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログインページ</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
  <header class="site-header">
    <div class="logo-wrapper">
      <img src="{{ asset('logo.svg') }}" alt="COACHTECH" class="logo">
    </div>
  </header>

  @yield('content')
</body>
</html>
