<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Attendance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        Log::warning('Clock-in validation failed', [
            'employee_id' => $this->user()?->employee?->id,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['evidence.photo']),
        ]);

        throw new ValidationException($validator);
    }

    public function rules(): array
    {
        // Allow 2 minutes tolerance for clock skew between mobile and server
        $maxAllowedTime = now()->addMinutes(2)->toIso8601String();
        
        return [
            'clock_in_at' => ['required', 'date', 'before_or_equal:'.$maxAllowedTime],
            'evidence' => ['required', 'array'],
            'evidence.geolocation' => ['required', 'array'],
            'evidence.geolocation.lat' => ['required', 'numeric', 'between:-90,90'],
            'evidence.geolocation.lng' => ['required', 'numeric', 'between:-180,180'],
            'evidence.geolocation.accuracy' => ['nullable', 'numeric', 'min:0'],
            'evidence.device' => ['required', 'array'],
            'evidence.device.device_id' => ['required', 'string', 'max:255'],
            'evidence.device.model' => ['required', 'string', 'max:255'],
            'evidence.device.os' => ['required', 'string', 'max:50'],
            'evidence.device.app_version' => ['required', 'string', 'max:20'],
            'evidence.photo' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in_at.required' => 'Waktu clock in wajib diisi.',
            'clock_in_at.before_or_equal' => 'Waktu clock in tidak boleh di masa depan.',
            'evidence.geolocation.lat.required' => 'Lokasi latitude wajib diisi.',
            'evidence.geolocation.lat.between' => 'Latitude harus antara -90 dan 90.',
            'evidence.geolocation.lng.required' => 'Lokasi longitude wajib diisi.',
            'evidence.geolocation.lng.between' => 'Longitude harus antara -180 dan 180.',
            'evidence.device.device_id.required' => 'ID perangkat wajib diisi.',
            'evidence.device.model.required' => 'Model perangkat wajib diisi.',
            'evidence.device.os.required' => 'Sistem operasi wajib diisi.',
            'evidence.device.app_version.required' => 'Versi aplikasi wajib diisi.',
            'evidence.photo.max' => 'Foto maksimal 5MB.',
            'evidence.photo.mimes' => 'Foto harus berformat JPG, JPEG, atau PNG.',
        ];
    }
}
