<?php

namespace App\Http\Requests;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class createTeacher extends FormRequest
{
    use ResponseTrait;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|min:3|max:100|regex:/^[\pL\s\-]+$/u',
            'username' => 'required|string|min:4|max:50|alpha_dash|unique:teachers,username',
            'email' => 'required|email|max:100|unique:teachers,email',
            'password' => 'required|string|min:8',
        ]
        ;
    }
    public function failedValidation(Validator $validator)
    {
        return $this->returnError($validator->errors()->first(), 400);
    }
}
