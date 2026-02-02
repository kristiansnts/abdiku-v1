<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Attendance;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', Rule::enum(AttendanceRequestType::class)],
            'attendance_raw_id' => ['nullable', 'exists:attendance_raw,id'],
            'date' => ['required_if:request_type,MISSING', 'date', 'before_or_equal:today'],
            'requested_clock_in_at' => ['nullable', 'date'],
            'requested_clock_out_at' => ['nullable', 'date', 'after:requested_clock_in_at'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Tipe permintaan wajib diisi.',
            'request_type.enum' => 'Tipe permintaan tidak valid.',
            'attendance_raw_id.exists' => 'Data kehadiran tidak ditemukan.',
            'date.required_if' => 'Tanggal wajib diisi untuk permintaan absen hilang.',
            'date.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'requested_clock_out_at.after' => 'Waktu clock out harus setelah waktu clock in.',
            'reason.required' => 'Alasan wajib diisi.',
            'reason.max' => 'Alasan maksimal 1000 karakter.',
        ];
    }
}
