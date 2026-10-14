<?php

return [

    // バリデーションエラーメッセージ
    'required' => ':attributeを入力してください',
    'email' => ':attributeはメール形式で入力してください',

    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
    ],

    'confirmed' => ':attributeと一致しません',

    // フィールド名の日本語化
    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
    ],

    // ルールごとの個別カスタムメッセージ
    'custom' => [
        'password' => [
            'confirmed' => 'パスワードと一致しません',
        ],
    ],

];
