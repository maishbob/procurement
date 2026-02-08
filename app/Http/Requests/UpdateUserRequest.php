<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin') || $this->user()->hasPermission('manage_users');
    }
    public function rules(): array
    {
        $user = $this->route('user');
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['nullable', 'string', 'max:20'], 'department_id' => ['required', 'exists:departments,id'], 'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['exists:roles,name'], 'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],];
    }
}
