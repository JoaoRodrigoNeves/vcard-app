<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
            'name' => 'required|string',
            'email' => 'required|email',
            'user_type' => ['required', Rule::in('V')],
            'old_password' => ['nullable', 'required_with:password,confirm_password', function($attribute, $value, $fail) {
                $user = Auth::guard('api')->user();
                if (!Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['nullable', Password::min(3), 'different:old_password'],
            'confirm_password' => 'nullable|same:password',
            'old_confirmation_code' => ['nullable', 'numeric', 'required_with:confirmation_code,confirm_confirmation_code', function($attribute, $value, $fail) {
                $user = Auth::guard('api')->user();
                if (!Hash::check($value, $user->confirmation_code)) {
                    $fail('The current confirmation code is incorrect.');
                }
            }],
            'confirmation_code' => ['nullable', 'numeric', 'different:old_confirmation_code'],
            'confirm_confirmation_code' => 'nullable|same:confirmation_code',
            'base64ImagePhoto' => 'nullable|string',
            'deletePhotoOnServer' => 'nullable|boolean',
        ];
    }
}
