@component('mail::message')
# ログイン確認

{{ $user->name ?? 'ユーザー' }} 様

以下のボタンをクリックすると、ログインが完了します。  
**有効期限：{{ $expiresAt->format('Y-m-d H:i') }} まで**

@component('mail::button', ['url' => $signedUrl])
ログインを完了する
@endcomponent

※心当たりがない場合は、このメールを破棄してください。

@endcomponent
