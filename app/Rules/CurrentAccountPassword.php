<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class CurrentAccountPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Auth::check() || ! Auth::getProvider()->validateCredentials(Auth::user(), ['password' => $value])) {
            $fail('The password is incorrect.');
        }
    }
}
