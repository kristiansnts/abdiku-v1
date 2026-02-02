<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_out_at' => ['required', 'date', 'before_or_equal:now'],
            'evidence' => ['nullable', 'array'],
            'evidence.geolocation' => ['nullable', 'array'],
            'evidence.geolocation.lat' => ['required_with:evidence.geolocation', 'numeric', 'between:-90,90'],
            'evidence.geolocation.lng' => ['required_with:evidence.geolocation', 'numeric', 'between:-180,180'],
            'evidence.geolocation.accuracy' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_out_at.required' => 'Waktu clock out wajib diisi.',
            'clock_out_at.before_or_equal' => 'Waktu clock out tidak boleh di masa depan.',
            'evidence.geolocation.lat.required_with' => 'Latitude wajib diisi jika mengirim geolokasi.',
            'evidence.geolocation.lat.between' => 'Latitude harus antara -90 dan 90.',
            'evidence.geolocation.lng.required_with' => 'Longitude wajib diisi jika mengirim geolokasi.',
            'evidence.geolocation.lng.between' => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
