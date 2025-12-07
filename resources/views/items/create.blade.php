{{-- resources/views/items/create.blade.php --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
{{-- ※ 他ページ同様の .error スタイルは共通CSSにある想定（未定義ならそちらへ追加） --}}
@endsection

@section('content')
@php
  // "sell" バッグ優先。無ければ default へ。
  $bag = $errors->getBag('sell');
  if ($bag->isEmpty()) {
      $bag = $errors->getBag('default');
  }
@endphp

<div class="sell-container">
  <h2 class="page-title">商品の出品</h2>

  <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="sell-form">
    @csrf

    {{-- 商品画像 --}}
    <div class="form-section">
      <label class="form-label">商品画像</label>
      <div class="image-upload-box">
        <label class="upload-button">
          画像を選択する
          <input type="file" name="image" accept="image/*" onchange="previewImage(event)">
        </label>
        <div class="image-preview-wrapper">
          <img id="preview" class="image-preview" alt="プレビュー画像" style="display:none">
        </div>
      </div>
      @if ($bag->has('image'))
        <div class="error">{{ $bag->first('image') }}</div>
      @endif
    </div>

    <h3 class="section-heading">商品の詳細</h3>

    {{-- カテゴリー（複数選択） --}}
    <div class="form-section">
      <label class="form-label">カテゴリー</label>
      <div class="category-buttons" id="category-container">
        @php $oldIds = old('category_ids', []); @endphp
        @foreach ($categories as $category)
          <label class="category-label">
            <input
              type="checkbox"
              name="category_ids[]"
              value="{{ $category->id }}"
              {{ in_array($category->id, $oldIds, true) ? 'checked' : '' }}>
            {{ $category->name }}
          </label>
        @endforeach
      </div>
      @if ($bag->has('category_ids'))
        <div class="error">{{ $bag->first('category_ids') }}</div>
      @endif
      {{-- 要素ごとのエラーも表示 --}}
      @foreach ($bag->get('category_ids.*', []) as $msg)
        <div class="error">{{ $msg }}</div>
      @endforeach
    </div>

    {{-- 商品の状態 --}}
    <div class="form-section">
      <label class="form-label">商品の状態</label>
      <select name="condition_id" class="form-select">
        <option value="" disabled {{ old('condition_id') ? '' : 'selected' }} hidden>選択してください</option>
        @foreach ($conditions as $condition)
          <option
            value="{{ $condition->id }}"
            {{ (string)old('condition_id') === (string)$condition->id ? 'selected' : '' }}>
            {{ $condition->condition }}
          </option>
        @endforeach
      </select>
      @if ($bag->has('condition_id'))
        <div class="error">{{ $bag->first('condition_id') }}</div>
      @endif
    </div>

    <h3 class="section-heading">商品名と説明</h3>

    {{-- 商品名 --}}
    <div class="form-section">
      <label class="form-label">商品名</label>
      <input type="text" name="name" class="form-input" value="{{ old('name') }}">
      @if ($bag->has('name'))
        <div class="error">{{ $bag->first('name') }}</div>
      @endif
    </div>

    {{-- ブランド名（任意） --}}
    <div class="form-section">
      <label class="form-label">ブランド名</label>
      <input type="text" name="brand" class="form-input" value="{{ old('brand') }}">
      @if ($bag->has('brand'))
        <div class="error">{{ $bag->first('brand') }}</div>
      @endif
    </div>

    {{-- 商品の説明 --}}
    <div class="form-section">
      <label class="form-label">商品の説明</label>
      <textarea name="description" class="form-textarea">{{ old('description') }}</textarea>
      @if ($bag->has('description'))
        <div class="error">{{ $bag->first('description') }}</div>
      @endif
    </div>

    {{-- 販売価格 --}}
    <div class="form-section">
      <label class="form-label">販売価格</label>
      <div class="price-input">
        <input
          type="number"
          name="price"
          class="form-input"
          value="{{ old('price') }}"
          inputmode="numeric"
          min="0"
          step="1">
      </div>
      @if ($bag->has('price'))
        <div class="error">{{ $bag->first('price') }}</div>
      @endif
    </div>

    {{-- 出品ボタン --}}
    <div class="form-section">
      <button type="submit" class="submit-button">出品する</button>
    </div>
  </form>
</div>
@endsection

@section('js')
<script>
function previewImage(event) {
  const preview = document.getElementById('preview');
  const file = event.target.files && event.target.files[0];
  if (!file) {
    preview.style.display = 'none';
    preview.src = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = () => {
    preview.src = reader.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

document.addEventListener('DOMContentLoaded', () => {
  // カテゴリ選択の見た目切り替え
  const chips = document.querySelectorAll('.category-label input[type="checkbox"]');
  chips.forEach(cb => {
    const label = cb.closest('.category-label');
    const sync = () => label && label.classList.toggle('selected', cb.checked);
    cb.addEventListener('change', sync);
    sync(); // 初期反映
  });
});
</script>
@endsection
