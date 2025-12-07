<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 認証済みのみ許可（ルートでもauthを掛けている前提）
        return auth()->check();
    }

    /**
     * バリデーション前の軽い整形：
     * - 前後の空白を除去
     * - 改行はそのまま（文字数カウント対象）
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge([
                'content' => trim((string) $this->input('content')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // bail: 最初のエラーで打ち切り → メッセージが分かりやすい
            'content' => [
                'bail',
                'required',
                'string',          // 文字列として扱う
                'max:255',         // ★ 256文字以上で必ずエラー
                'not_regex:/^\s*$/u', // 空白だけを弾く（全角空白も対象）
            ],
        ];
    }

    public function attributes(): array
    {
        return ['content' => 'コメント'];
    }

    public function messages(): array
    {
        return [
            'content.required'  => 'コメントを入力してください。',
            'content.string'    => 'コメントを正しく入力してください。',
            'content.max'       => 'コメントは255文字以内で入力してください。',
            'content.not_regex' => '空白のみのコメントは送信できません。',
        ];
    }

    /**
     * 失敗時は商品詳細の #comments へ戻す（UX向上）
     */
    protected function getRedirectUrl(): string
    {
        return url()->previous() . '#comments';
    }
}
