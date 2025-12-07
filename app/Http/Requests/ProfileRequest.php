<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 画像は任意。拡張子とサイズで制限（サーバ側で厳密チェック）
            'profile_image' => 'nullable|file|mimes:jpeg,jpg,png',

            'name'          => 'required|string|max:20',
            'postal_code'   => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'address'       => 'required|string|max:255',
            'building_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            // ★ ルールに合わせて key も file / mimes / max に
            'profile_image.file'  => 'ファイルを選択してください。',
            'profile_image.mimes' => '画像ファイルはjpegまたはpng形式で指定してください。',
         

            'name.required'       => 'ユーザー名を入力してください',
            'name.max'            => 'ユーザー名は20文字以内で入力してください',

            'postal_code.required'=> '郵便番号を入力してください',
            // ← 正しくは 7 桁なので文言を修正
            'postal_code.regex'   => '郵便番号はハイフンありの7桁（例: 123-4567）で入力してください',

            'address.required'    => '住所を入力してください',
            'address.max'         => '住所は255文字以内で入力してください',
            'building_name.max'   => '建物名は255文字以内で入力してください',
        ];
    }
}
