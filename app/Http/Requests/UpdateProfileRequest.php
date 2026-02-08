<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Users can only update their own profile
        return $this->user()->id === $this->route('user')?->id || $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $user = $this->route('user') ?? $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'preferred_locale' => [Rule::in(['en', 'sw'])],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already in use.',
        ];
    }
}
