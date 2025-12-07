@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/verify.css') }}?v={{ filemtime(public_path('css/verify.css')) }}">
@endsection

@section('content')
<div class="verify-wrap">
  <div class="verify-card">
    <h1 class="verify-title">登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。</h1>

    {{-- 開発環境（MailHog）向けのショートカットボタン --}}
    @if (app()->environment('local'))
      <a class="verify-primary-btn" href="http://localhost:8025" target="_blank" rel="noopener">
        認証はこちらから
      </a>
    @else
      {{-- 本番などでは説明だけ（メール内リンクをクリックしてもらう） --}}
      <div class="verify-primary-btn disabled" aria-disabled="true">
        認証はこちらから
      </div>
    @endif

    {{-- ステータス表示（再送後など） --}}
    @if (session('status') === 'verification-link-sent')
      <p class="verify-status">確認リンクを再送しました。メールをご確認ください。</p>
    @endif

    {{-- 再送リンク --}}
    <form class="verify-resend" method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="verify-resend-link">認証メールを再送する</button>
    </form>
  </div>
</div>
@endsection
