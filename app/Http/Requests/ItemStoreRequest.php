<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'image'          => 'required|file|mimes:jpeg,jpg,png,webp|max:4096',
            'category_ids'   => ['required','array','min:1'],
            'category_ids.*' => ['integer','exists:categories,id'],
            'condition_id'   => ['required','integer','exists:conditions,id'],
            'name'           => ['required','string','max:50'],
            'brand'          => ['nullable','string','max:50'],
            'description'    => ['required','string','max:1000'],
            'price'          => ['required','integer','min:1','max:9999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required'        => '商品画像を選択してください',
            'image.mimes'           => '画像は jpg / jpeg / png / webp のみアップロード可能です',
            'image.max'             => '画像は 4MB 以下にしてください',
            'category_ids.required' => 'カテゴリーを1つ以上選択してください',
            'category_ids.min'      => 'カテゴリーを1つ以上選択してください',
            'category_ids.*.exists' => '存在しないカテゴリーが含まれています',
            'condition_id.required' => '商品の状態を選択してください',
            'name.required'         => '商品名を入力してください',
            'description.required'  => '商品の説明を入力してください',
            'price.required'        => '販売価格を入力してください',
            'price.integer'         => '販売価格は半角数字で入力してください',
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
}
