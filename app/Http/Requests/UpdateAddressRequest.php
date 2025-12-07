<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認証ミドルウェアで保護されている前提
    }

    /**
     * バリデーション前の入力整形
     * - 全角数字→半角
     * - 空白削除
     * - 長音「ー/－/―」→ ハイフンに正規化
     */
    protected function prepareForValidation(): void
    {
        $postal = (string) $this->input('delivery_postal_code', '');
        // 全角数字→半角（mbstring）
        if (function_exists('mb_convert_kana')) {
            $postal = mb_convert_kana($postal, 'n');
        }
        // 長音を半角ハイフンに統一
        $postal = str_replace(['ー', '－', '―'], '-', $postal);
        // 空白除去
        $postal = preg_replace('/\s+/u', '', $postal);

        $this->merge([
            'delivery_postal_code'   => $postal,
            'delivery_address'       => trim((string) $this->input('delivery_address', '')),
            'delivery_building_name' => trim((string) $this->input('delivery_building_name', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            // 123-4567 or 1234567 を許可
            'delivery_postal_code'   => ['required', 'regex:/^\d{3}-?\d{4}$/'],
            'delivery_address'       => ['required', 'string', 'max:255'],
            'delivery_building_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_postal_code.required' => '郵便番号を入力してください',
            'delivery_postal_code.regex'    => '郵便番号は123-4567（ハイフンあり/なし可）の形式で入力してください',
            'delivery_address.required'     => '住所を入力してください',
            'delivery_address.max'          => '住所は:max文字以内で入力してください',
            'delivery_building_name.max'    => '建物名は:max文字以内で入力してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'delivery_postal_code'   => '郵便番号',
            'delivery_address'       => '住所',
            'delivery_building_name' => '建物名',
        ];
    }
}
