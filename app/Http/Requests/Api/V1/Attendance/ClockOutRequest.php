<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Attendance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        Log::warning('Clock-out validation failed', [
            'employee_id' => $this->user()?->employee?->id,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);

        throw new ValidationException($validator);
    }

    public function rules(): array
    {
        // Allow 2 minutes tolerance for clock skew between mobile and server
        $maxAllowedTime = now()->addMinutes(2)->toIso8601String();
        
        return [
            'clock_out_at' => ['required', 'date', 'before_or_equal:'.$maxAllowedTime],
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
