{{-- resources/views/purchase/show.blade.php --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}?v={{ filemtime(public_path('css/purchase.css')) }}">
@endsection

@section('content')
@php
    use Illuminate\Support\Str;

    // サムネイル
    $thumb = $item->image_path
        ? (Str::startsWith($item->image_path, ['http://','https://']) ? $item->image_path : asset('storage/'.$item->image_path))
        : asset('images/placeholder.png');

    // ============================
    // 配送先:
    // 1) その商品向けに編集済みなら session('delivery') を使用
    // 2) それ以外（初回表示など）はプロフィールを初期値にする
    // ============================
    $sd           = session('delivery');
    $sdItemId     = session('delivery_item_id');    // <- どの商品用に更新したか
    $useSession   = is_array($sd) && ((int)$sdItemId === (int)$item->id);

    $dPostal   = $useSession
        ? ($sd['delivery_postal_code']   ?? '')
        : (optional($profile)->postal_code   ?? '');
    $dAddress  = $useSession
        ? ($sd['delivery_address']       ?? '')
        : (optional($profile)->address       ?? '');
    $dBuilding = $useSession
        ? ($sd['delivery_building_name'] ?? '')
        : (optional($profile)->building_name ?? '');

    // 支払い方法（UI表示用）
    $payOld   = old('pay_method', 'convenience_store');
    $payLabel = $payOld === 'credit_card' ? 'クレジットカード' : ($payOld === 'bank_transfer' ? '銀行振込' : 'コンビニ払い');
@endphp

<div class="purchase-wrap">

  @if (session('status'))
    <div class="purchase-status">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="purchase-errors">
      <ul>
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('purchase.checkout', $item) }}" class="purchase-grid" id="purchase-form">
    @csrf

    {{-- 左カラム --}}
    <div class="purchase-left">

      {{-- 商品 --}}
      <div class="purchase-itembox">
        <div class="purchase-thumb">
          <img src="{{ $thumb }}" alt="{{ $item->name }}">
        </div>
        <div class="purchase-iteminfo">
          <div class="purchase-itemname">{{ $item->name }}</div>
          <div class="purchase-itemprice">¥{{ number_format($item->price) }}</div>
        </div>
      </div>

      {{-- 数量は1固定 --}}
      <input type="hidden" name="quantity" value="1">

      {{-- 支払い方法 --}}
      <div class="purchase-block pay-block">
        <div class="purchase-blocktitle">支払い方法</div>
        <div class="purchase-field">
          <select name="pay_method" class="purchase-select" id="js-pay">
            <option value="convenience_store" {{ $payOld==='convenience_store' ? 'selected' : '' }}>コンビニ払い</option>
            <option value="credit_card"       {{ $payOld==='credit_card'       ? 'selected' : '' }}>カード支払い</option>
            <option value="bank_transfer"     {{ $payOld==='bank_transfer'     ? 'selected' : '' }}>銀行振込</option>
          </select>
        </div>
        @error('pay_method') <div class="purchase-error">{{ $message }}</div> @enderror
      </div>

      {{-- 配送先（プロフィール初期値／編集後はセッション） --}}
      <div class="purchase-block ship-block">
        <div class="purchase-blocktitle purchase-flex">
          <span>配送先</span>
          <a href="{{ route('address.edit', ['item' => $item->id]) }}" class="purchase-editlink">変更する</a>
        </div>

        <div class="purchase-address">
          <div class="purchase-addressline">〒 {{ $dPostal ?: 'XXX-XXXX' }}</div>
          <div class="purchase-addressline">{{ $dAddress ?: 'ここに住所が表示されます' }}</div>
          @if($dBuilding)
            <div class="purchase-addressline">{{ $dBuilding }}</div>
          @endif
        </div>

        {{-- Stripe に送る値（delivery_* 固定） --}}
        <input type="hidden" name="delivery_postal_code"   value="{{ $dPostal }}">
        <input type="hidden" name="delivery_address"       value="{{ $dAddress }}">
        <input type="hidden" name="delivery_building_name" value="{{ $dBuilding }}">

        @error('delivery_postal_code') <div class="purchase-error">{{ $message }}</div> @enderror
        @error('delivery_address')     <div class="purchase-error">{{ $message }}</div> @enderror
      </div>

    </div>

    {{-- 右カラム --}}
    <div class="purchase-right">
      <div class="purchase-summary">
        <div class="purchase-summary-row">
          <div class="label">商品代金</div>
          <div class="value">¥{{ number_format($item->price) }}</div>
        </div>
        <div class="purchase-summary-row">
          <div class="label">支払い方法</div>
          <div class="value" id="js-paylabel">{{ $payLabel }}</div>
        </div>
      </div>

      <button type="submit" class="purchase-submit">購入する</button>
    </div>

  </form>
</div>
@endsection

@section('js')
<script>
  // 支払い方法ラベルの同期
  const paySel   = document.getElementById('js-pay');
  const payLabel = document.getElementById('js-paylabel');

  function updatePayLabel(val){
    payLabel.textContent =
      val === 'credit_card'   ? 'カード支払い' :
      val === 'bank_transfer' ? '銀行振込' : 'コンビニ払い';
  }

  if (paySel) {
    updatePayLabel(paySel.value);
    paySel.addEventListener('change', () => updatePayLabel(paySel.value));
  }
</script>
@endsection
