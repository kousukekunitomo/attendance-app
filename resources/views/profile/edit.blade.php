@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="profile-container">
    <h2 class="profile-title">プロフィール設定</h2>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form">
        @csrf

        {{-- プロフィール画像 --}}
        <div class="image-upload">
            <img
                src="{{ optional($profile)->image_url ?? asset('images/placeholder.png') }}"
                class="profile-image"
                id="preview"
                alt="プロフィール画像"
                onerror="this.src='{{ asset('images/placeholder.png') }}'"
            >
            <label class="image-button">
              画像を選択する
              <input type="file" name="profile_image" id="profile_image_input" accept=".jpg,.jpeg,.png">
            </label>
            @error('profile_image')
              <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        {{-- ユーザー名 --}}
        <div class="form-group">
            <label for="name">ユーザー名</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name">
            @error('name')
              <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        {{-- 郵便番号 --}}
        <div class="form-group">
            <label for="postal_code">郵便番号</label>
            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $profile->postal_code ?? '') }}" autocomplete="postal-code">
            @error('postal_code')
              <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        {{-- 住所 --}}
        <div class="form-group">
            <label for="address">住所</label>
            <input type="text" id="address" name="address" value="{{ old('address', $profile->address ?? '') }}" autocomplete="street-address">
            @error('address')
              <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        {{-- 建物名 --}}
        <div class="form-group">
            <label for="building_name">建物名</label>
            <input type="text" id="building_name" name="building_name" value="{{ old('building_name', $profile->building_name ?? '') }}">
            @error('building_name')
              <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="submit-button">更新する</button>
    </form>
</div>
@endsection

@section('js')
<script>
(() => {
  const input = document.getElementById('profile_image_input');
  const preview = document.getElementById('preview');
  const wrap = input.closest('.image-upload');
  const initialSrc = preview.getAttribute('src');

  const okTypes = ['image/jpeg', 'image/png'];
  const okExts  = ['jpg', 'jpeg', 'png'];
  const maxBytes = 2 * 1024 * 1024; // 2MB

  function removeClientError() {
    const el = wrap.querySelector('.client-error');
    if (el) el.remove();
  }
  function showClientError(msg) {
    removeClientError();
    const div = document.createElement('div');
    div.className = 'error-message client-error';
    div.textContent = msg;
    wrap.appendChild(div);
  }

  input.addEventListener('change', (e) => {
    const file = e.target.files[0];
    removeClientError();
    if (!file) return;

    const typeOK = okTypes.includes(file.type);
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    const extOK = okExts.includes(ext);

    if (!typeOK && !extOK) {
      showClientError('画像ファイルはjpegまたはpng形式で指定してください。');
      input.value = '';
      preview.src = initialSrc;
      return;
    }


    const reader = new FileReader();
    reader.onload = () => { preview.src = reader.result; };
    reader.readAsDataURL(file);
  });
})();
</script>
@endsection
