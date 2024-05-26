<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class VCardRegisterRequest extends FormRequest
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
            'username' => 'required',
            'password' => ['required', Password::min(3)],
            'confirm_password' => 'required|same:password',
            'email' => 'required|email',
            'confirmation_code' => ['required', 'numeric'],
            'confirm_confirmation_code' => 'required|same:confirmation_code',
            'base64ImagePhoto' => 'nullable|string',
        ];
    }
}
