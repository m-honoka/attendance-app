<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        // 必須・8文字以上・確認用パスワード（password_confirmation）との一致
        return ['required', 'string', Password::default()->min(8), 'confirmed'];
    }
}
