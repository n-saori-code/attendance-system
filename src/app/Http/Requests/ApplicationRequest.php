<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'       => ['required', 'date_format:H:i'],
            'clock_out'      => ['required', 'date_format:H:i'],
            'break_start.0'  => ['required', 'date_format:H:i'],
            'break_end.0'    => ['required', 'date_format:H:i'],
            'break_start.*'  => ['nullable', 'date_format:H:i'],
            'break_end.*'    => ['nullable', 'date_format:H:i'],
            'remarks'        => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn  = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            /** --------------------
             * 出勤・退勤時間
             * -------------------- */
            if ($clockIn && $clockOut && strtotime($clockIn) >= strtotime($clockOut)) {
                $validator->errors()->add(
                    'clock_in',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            $breakStarts = $this->input('break_start', []);
            $breakEnds   = $this->input('break_end', []);

            foreach ($breakStarts as $i => $start) {
                $end = $breakEnds[$i] ?? null;

                if ($start && !$end) {
                    $validator->errors()->add("break_end.$i", '休憩終了時間を入力してください');
                }

                if ($end && !$start) {
                    $validator->errors()->add("break_start.$i", '休憩開始時間を入力してください');
                }

                /** --------------------
                 * 休憩開始 < 出勤
                 * 休憩開始 > 退勤
                 * -------------------- */
                if ($start && $clockIn && strtotime($start) < strtotime($clockIn)) {
                    $validator->errors()->add(
                        "break_start.$i",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($start && $clockOut && strtotime($start) > strtotime($clockOut)) {
                    $validator->errors()->add(
                        "break_start.$i",
                        '休憩時間が不適切な値です'
                    );
                }

                /** --------------------
                 * 休憩終了 > 退勤
                 * -------------------- */
                if ($end && $clockOut && strtotime($end) > strtotime($clockOut)) {
                    $validator->errors()->add(
                        "break_end.$i",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'clock_in.required'      => '出勤時間を入力してください',
            'clock_out.required'     => '退勤時間を入力してください',
            'clock_in.date_format'   => '時間は00:00の形式で記入してください',
            'clock_out.date_format'  => '時間は00:00の形式で記入してください',

            'break_start.0.required' => '休憩開始時間を入力してください',
            'break_end.0.required'   => '休憩終了時間を入力してください',

            'remarks.required'      => '備考を記入してください',
            'remarks.max'           => '備考は255文字以内で入力してください',
        ];
    }
}
