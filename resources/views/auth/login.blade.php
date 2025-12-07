@extends('layouts.guest')

@section('content')
<div class="login-container">
  <div class="form-area">
    <h2 class="login-title">ログイン</h2>

    <form method="POST" action="{{ route('login') }}" class="login-form" novalidate>
      @csrf
      {{-- 元の遷移先を保持（購入ページなどに復帰） --}}
      <input type="hidden" name="intended" value="{{ request('intended', old('intended')) }}">

      <div class="form-group">
        <label for="email">メールアドレス</label>
        <input id="email" type="email" name="email"
               value="{{ old('email') }}"
               autocomplete="email" autofocus
               class="input @error('email') is-error @enderror">
        @error('email')
          <div class="error" role="alert" aria-live="polite">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-group">
        <label for="password">パスワード</label>
        <input id="password" type="password" name="password"
               autocomplete="current-password"
               class="input @error('password') is-error @enderror">
        @error('password')
          <div class="error" role="alert" aria-live="polite">{{ $message }}</div>
        @enderror
      </div>

      <button type="submit" class="login-btn">ログインする</button>
    </form>

    <div class="register-link">
      {{-- 会員登録後も元の行き先へ戻したい場合は intended を引き継ぐ --}}
      <a href="{{ route('register', ['intended' => request('intended')]) }}">会員登録はこちら</a>
    </div>
  </div>
</div>
@endsection
