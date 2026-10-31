<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCorrectionRequest extends FormRequest
{
    /**
     * リクエストを許可する
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 基本的なバリデーションルール
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i'],

            'new_break_in' => ['present', 'array'],
            'new_break_out' => ['present', 'array'],

            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],

            'comment' => ['required', 'string'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください。',
            'new_clock_out.required' => '退勤時間を入力してください。',
            'new_clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.date_format' => '休憩時間が不適切な値です',
            'new_break_out.*.date_format' => '休憩時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ];
    }

    /**
     * 時刻同士の前後関係を検証する
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            // 出勤・退勤の必須チェックや形式チェックに失敗した場合は比較しない
            if (
                $validator->errors()->has('new_clock_in') ||
                $validator->errors()->has('new_clock_out')
            ) {
                return;
            }

            $clockIn = $data['new_clock_in'];
            $clockOut = $data['new_clock_out'];

            // 出勤時間が退勤時間以降の場合
            if ($clockIn >= $clockOut) {
                $validator->errors()->add(
                    'new_clock_in',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            $breakIns = $data['new_break_in'] ?? [];
            $breakOuts = $data['new_break_out'] ?? [];

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                // 開始・終了とも空欄なら、この休憩欄は使用しない
                if (blank($breakIn) && blank($breakOut)) {
                    continue;
                }

                // 開始・終了の片方だけが入力されている場合
                // 指定されたエラーメッセージを流用する
                if (blank($breakIn) || blank($breakOut)) {
                    $validator->errors()->add(
                        blank($breakIn)
                        ? "new_break_in.$index"
                        : "new_break_out.$index",
                        '休憩時間が不適切な値です'
                    );

                    continue;
                }

                // 時刻形式に問題がある項目は比較しない
                if (
                    $validator->errors()->has("new_break_in.$index") ||
                    $validator->errors()->has("new_break_out.$index")
                ) {
                    continue;
                }

                // 休憩開始が出勤時間より前の場合
                if ($breakIn < $clockIn) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );
                }

                // 休憩開始が退勤時間より後の場合
                if ($breakIn > $clockOut) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );
                }

                // 休憩終了が退勤時間より後の場合
                if ($breakOut > $clockOut) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}
