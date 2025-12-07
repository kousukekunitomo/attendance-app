@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/verify.css') }}?v={{ filemtime(public_path('css/verify.css')) }}">
@endsection

@section('content')
<div class="verify-wrap">
  <div class="verify-card">
    <h1 class="verify-title">
      ログイン確認メールを送信しました。<br>
      メール内のリンクをクリックしてログインを完了してください。
    </h1>

    {{-- 開発環境（MailHog）ショートカット --}}
    @if (app()->environment('local'))
      <a class="verify-primary-btn" href="http://localhost:8025" target="_blank" rel="noopener">
        認証はこちらから
      </a>
    @else
      <div class="verify-primary-btn disabled" aria-disabled="true">
        認証はこちらから
      </div>
    @endif

    {{-- ★ テキストの表示は削除（session('status')は出さない） --}}

    {{-- ★「ログインに戻る」を → 「認証メールを再送する」に変更（下線なし） --}}
    <form class="verify-resend" method="POST" action="{{ route('login.challenge.resend') }}">
      @csrf
      <button type="submit" class="verify-resend-link no-underline">認証メールを再送する</button>
    </form>
  </div>
</div>
@endsection
