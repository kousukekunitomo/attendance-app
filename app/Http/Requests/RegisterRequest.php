<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'  => ['required', 'string', 'max:20'],

            // ★ 重複チェックを追加
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
            ],

            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages()
    {
        return [
            'name.required'  => 'お名前を入力してください',
            'name.max'       => 'お名前は20文字以内で入力してください',

            'email.required' => 'メールアドレスを入力してください',
            'email.email'    => 'メールアドレスはメール形式で入力してください',
            'email.unique'   => 'このメールアドレスはすでに使用されています',

            'password.required' => 'パスワードを入力してください',
            'password.min'      => 'パスワードは8文字以上で入力してください',

            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.same'     => 'パスワードと一致しません',
        ];
    }
}
