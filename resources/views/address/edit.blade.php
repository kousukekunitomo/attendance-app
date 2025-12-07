{{-- resources/views/address/edit.blade.php --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/address.css') }}?v={{ filemtime(public_path('css/address.css')) }}">
@endsection

@section('content')
<div class="address-wrap">
  <h2 class="address-title">住所の変更</h2>

  @if (session('status'))
    <div class="flash ok">{{ session('status') }}</div>
  @endif

  <div class="address-card">
    {{-- ★ $item を必ず渡す（戻り先で使用） --}}
    <form method="POST" action="{{ route('address.update', $item) }}" class="address-form">
      @csrf

      @php
        // ★ 初期値は常にプロフィール（バリデーション失敗時のみ old() 優先）
        $dPostal   = old('delivery_postal_code',   optional($profile)->postal_code   ?? '');
        $dAddress  = old('delivery_address',       optional($profile)->address       ?? '');
        $dBuilding = old('delivery_building_name', optional($profile)->building_name ?? '');
      @endphp

      <div class="form-rows">
        {{-- 郵便番号 --}}
        <div class="form-row">
          <label class="form-label" for="delivery_postal_code">郵便番号</label>
          <div>
            <input id="delivery_postal_code" name="delivery_postal_code" type="text"
              value="{{ $dPostal }}"
              class="input @error('delivery_postal_code') is-error @enderror"
              placeholder="123-4567" inputmode="numeric" autocomplete="postal-code">
            @error('delivery_postal_code') <div class="error-text">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- 住所 --}}
        <div class="form-row">
          <label class="form-label" for="delivery_address">住所</label>
          <div>
            <input id="delivery_address" name="delivery_address" type="text"
              value="{{ $dAddress }}"
              class="input @error('delivery_address') is-error @enderror"
              placeholder="都道府県・市区町村・番地" autocomplete="street-address">
            @error('delivery_address') <div class="error-text">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- 建物名（任意） --}}
        <div class="form-row">
          <label class="form-label" for="delivery_building_name">建物名</label>
          <div>
            <input id="delivery_building_name" name="delivery_building_name" type="text"
              value="{{ $dBuilding }}"
              class="input @error('delivery_building_name') is-error @enderror"
              placeholder="マンション名・部屋番号など">
            @error('delivery_building_name') <div class="error-text">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="form-row">
          <div class="form-label"></div>
          <div>
            <button type="submit" class="btn-submit">更新する</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
