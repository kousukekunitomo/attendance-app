<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    // 出品フォーム専用のエラーバッグ
    protected $errorBag = 'sell';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 画像：mimes を image より前に（bail が効き、拡張子違いで確実に mimes 文言を出す）
            'image'          => ['bail', 'required', 'file', 'mimes:jpeg,jpg,png', 'image'],

            'category_ids'   => ['bail', 'required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],

            'condition_id'   => ['bail', 'required', 'exists:conditions,id'],
            'name'           => ['bail', 'required', 'string', 'max:255'],
            'brand'          => ['nullable', 'string', 'max:255'],

            // ① 商品説明：255 文字上限（256 文字以上でエラーになる）
            'description'    => ['bail', 'required', 'string', 'max:255'],

            // ② 価格：未入力は不可（required）、0 円以上でOK
            'price'          => ['bail', 'required', 'integer', 'min:0', 'max:99999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'image'          => '商品画像',
            'category_ids'   => 'カテゴリー',
            'category_ids.*' => 'カテゴリー',
            'condition_id'   => '商品の状態',
            'name'           => '商品名',
            'brand'          => 'ブランド名',
            'description'    => '商品説明',
            'price'          => '価格',
        ];
    }

    public function messages(): array
    {
        return [
            // ③ 画像メッセージ：拡張子違いはこの文言を出す
            'image.required' => ':attributeを選択してください。',
            'image.file'     => ':attributeのアップロードに失敗しました。',
            'image.mimes'    => '拡張子が.jpegもしくは.pngの画像ファイルを選択してください',
            'image.image'    => '拡張子が.jpegもしくは.pngの画像ファイルを選択してください',

            'category_ids.required'   => ':attributeを1つ以上選択してください。',
            'category_ids.min'        => ':attributeを1つ以上選択してください。',
            'category_ids.*.distinct' => '同じ:attributeが重複しています。',
            'category_ids.*.exists'   => '存在しない:attributeが含まれています。',

            'condition_id.required'   => ':attributeを選択してください。',
            'name.required'           => ':attributeを入力してください。',
            'description.required'    => ':attributeを入力してください。',

            // ① 256 文字以上のときの文言
            'description.max'         => ':attributeは255文字以内で入力してください。',

            'price.required'          => ':attributeを入力してください。',
            'price.integer'           => ':attributeは半角数字で入力してください（カンマ不可）。',
            // ② 0 円以上を許容
            'price.min'               => ':attributeは:min以上で入力してください。',
            'price.max'               => ':attributeは:max以下で入力してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        // 価格："12,345" や 全角 → 半角数字に正規化。空なら null にして required を発火
        $price = (string) $this->input('price', '');
        $price = preg_replace('/[^\d]/u', '', mb_convert_kana($price, 'n', 'UTF-8'));
        $this->merge(['price' => $price === '' ? null : (int) $price]);
    }
}
