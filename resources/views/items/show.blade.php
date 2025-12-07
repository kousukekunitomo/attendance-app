@extends('layouts.app')

@php
  // このページでは共通の全体エラー表示を抑止（重複防止）
  $suppressGlobalErrors = true;

  $comments    = $item->relationLoaded('comments') ? $item->comments : ($item->comments ?? collect());
  $commentsCnt = $item->comments_count ?? $comments->count();
  $likesCount  = $item->likes_count ?? (method_exists($item, 'likedBy') ? $item->likedBy()->count() : 0);
  $liked       = isset($liked) ? $liked : (auth()->check() ? auth()->user()->hasLiked($item) : false);
  $isMyItem    = auth()->check() && (int)$item->user_id === (int)auth()->id();
  $isSold      = (int)($item->status ?? 1) === 0;
@endphp

@section('css')
  @php
    $cssPath = public_path('css/item-show.css');
    $ver     = file_exists($cssPath) ? filemtime($cssPath) : time();
  @endphp
  <link rel="stylesheet" href="{{ asset('css/item-show.css') }}?v={{ $ver }}">
@endsection

@section('content')
<div class="show-wrap">
  <div class="show-grid">

    {{-- 左：画像 --}}
    <div class="show-left">
      <div class="show-image">
        <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
             onerror="this.src='{{ asset('images/placeholder.png') }}'">
        @if($isSold)
          {{-- 三角 SOLD リボン（クリックを邪魔しない） --}}
          <span class="sold-corner" aria-label="SOLD"></span>
        @endif
      </div>
    </div>

    {{-- 右：本文 --}}
    <div class="show-right">

      {{-- タイトル・ブランド --}}
      <h1 class="show-title">{{ $item->name }}</h1>
      @if(!empty($item->brand_name))
        <div class="brand">{{ $item->brand_name }}</div>
      @endif

      {{-- 価格 --}}
      <div class="show-price-row">
        <div class="show-price">
          ¥{{ number_format($item->price) }} <span class="tax">（税込）</span>
        </div>
      </div>

      {{-- いいね／コメントアイコン --}}
      <div class="show-icons">
        <div class="icon-stack">
          @auth
            <form method="POST" action="{{ route('items.like.toggle', $item) }}">
              @csrf
              <button type="submit" class="icon-btn star {{ $liked ? 'active' : '' }}" aria-label="お気に入り">
                <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true">
                  <path class="star-path" d="M12 2l3.09 6.26L22 9.27l-5 4.88L18.18 22 12 18.6 5.82 22 7 14.15l-5-4.88 6.91-1.01L12 2z"/>
                </svg>
              </button>
            </form>
          @else
            <a href="{{ route('login', ['intended' => url()->current()]) }}" class="icon-btn star" aria-label="ログインしてお気に入り">
              <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true">
                <path class="star-path" d="M12 2l3.09 6.26L22 9.27l-5 4.88L18.18 22 12 18.6 5.82 22 7 14.15l-5-4.88 6.91-1.01L12 2z"/>
              </svg>
            </a>
          @endauth
          <div class="icon-count">{{ $likesCount }}</div>
        </div>

        <div class="icon-stack">
          <a href="#comments" class="icon-btn bubble" aria-label="コメントへ移動">
            <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true">
              <path class="bubble-path" d="M4 4h16v12H7l-3 3V4z"/>
            </svg>
          </a>
          <div class="icon-count">{{ $commentsCnt }}</div>
        </div>
      </div>

      {{-- 購入ボタン（type="button" で強制遷移） --}}
      <div class="show-cta">
        @if($isSold)
          <span class="buy-btn is-disabled">SOLD</span>
        @else
          @auth
            @if($isMyItem)
              <span class="buy-btn is-disabled">出品者は購入できません</span>
            @else
              <button type="button"
                      class="buy-btn"
                      onclick="window.location.href='{{ route('purchase.show', $item) }}'">
                購入手続きへ
              </button>
            @endif
          @else
            <a href="{{ route('login', ['intended' => route('purchase.show', $item)]) }}" class="buy-btn">購入手続きへ</a>
          @endauth
        @endif
      </div>

      {{-- 商品説明 --}}
      <div class="show-section">
        <div class="sec-title">商品説明</div>
        <div class="desc">{{ $item->description }}</div>
      </div>

      {{-- 商品の情報 --}}
      <div class="show-section">
        <div class="sec-title">商品の情報</div>
        <dl class="info-table">
          <div class="row">
            <dt>カテゴリー</dt>
            <dd>
              @forelse($item->categories ?? [] as $cat)
                <span class="chip">{{ $cat->name }}</span>
              @empty
                <span class="chip">未設定</span>
              @endforelse
            </dd>
          </div>
          <div class="row">
            <dt>商品の状態</dt>
            <dd>{{ $item->condition->condition ?? '不明' }}</dd>
          </div>
        </dl>
      </div>



{{-- コメント一覧 --}}
<div class="show-section" id="comments">
  <div class="sec-title">コメント（{{ $commentsCnt }}）</div>

  <div class="comment-list">
    @forelse($comments as $c)
      @php
        $avatar = optional($c->user->profile)->image_url ?? asset('images/placeholder.png');
      @endphp
      <div class="comment">
        <div class="comment-head">
          <img class="comment-avatar" src="{{ $avatar }}" alt="">
          <div class="comment-name">{{ $c->user->name ?? 'ユーザー' }}</div>
        </div>
        <div class="comment-body">{{ $c->content }}</div>
      </div>
    @empty
      <div class="comment empty">まだコメントはありません。</div>
    @endforelse
  </div>

  @php
    // named error bag（comment）→ 無ければ default を参照
    $err = $errors->getBag('comment');
    if ($err->isEmpty()) {
      $err = $errors->getBag('default');
    }
  @endphp

  {{-- コメント投稿フォーム --}}
@auth
  <form class="comment-form" method="POST" action="{{ route('items.comments.store', $item) }}" novalidate>
    @csrf
    <div class="form-group">
      <label for="commentContent" class="form-label">商品へのコメント</label>

      {{-- ★ maxlength を外す（>255 でも送れて、サーバ側で弾かれる） --}}
      <textarea
        id="commentContent"
        name="content"
        rows="4"
        class="comment-textarea @error('content') is-error @enderror"
        placeholder="この商品についてコメントする…"
      >{{ old('content') }}</textarea>

      {{-- サーバ側バリデーションのエラーメッセージを表示 --}}
      @php
        // まず named bag "comment" を見る → 無ければ default
        $errBag = $errors->getBag('comment');
        if ($errBag->isEmpty()) $errBag = $errors->getBag('default');
      @endphp
      @if ($errBag->has('content'))
        <div class="error">{{ $errBag->first('content') }}</div>
      @else
        {{-- 任意：ガイド表示（クライアント側はあくまで補助） --}}
        
      @endif
    </div>

    <button class="comment-btn" type="submit">コメントを送信する</button>
  </form>
@else
  <form class="comment-form" method="GET" action="{{ route('login') }}">
    <input type="hidden" name="intended" value="{{ url()->current() }}">
    <textarea placeholder="コメントを書く（ログインが必要です）" disabled></textarea>
    <button class="comment-btn" type="submit" aria-disabled="true">コメントを送信する</button>
  </form>
@endauth

</div>



    </div>
  </div>
</div>
@endsection
