<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected $errorBag = 'updateProfile';

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtoupper(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:32', Rule::unique(Account::class, 'email')->ignore($this->user()->id), Rule::unique(Account::class, 'username')->ignore($this->user()->id)],
            'current_password' => ['required', 'string', new \App\Rules\CurrentAccountPassword],
        ];
    }
}
