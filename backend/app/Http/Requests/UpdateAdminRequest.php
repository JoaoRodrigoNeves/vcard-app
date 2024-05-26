<?php

namespace App\Http\Requests;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required',
            'name' => 'required|string|max:50',
            'email' => ['email', 'required', 'string', 'max:90', function($attribute, $value, $fail) {
                $checkEmail = User::where('email', $value)->where('id', '!=', $this->input('id'))->first();
                if ($checkEmail) {
                    $fail('Email already in use.');
                }
            }],
            'old_password' => ['nullable', 'required_with:password,confirm_password', function($attribute, $value, $fail) {
                $user = Auth::guard('api')->user();
                if (!Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['nullable', Password::min(3), 'different:old_password'],
            'confirm_password' => 'nullable|same:password',
        ];
    }
}
