<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TableRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'draw' => 'sometimes|integer|min:0',
            'start' => 'sometimes|integer|min:0',
            'length' => 'sometimes|integer|between:1,100',
            'search.value' => 'nullable|string|max:100',
            'order.0.column' => 'sometimes|integer',
            'order.0.dir' => 'sometimes|in:asc,desc',
        ];
    }

    public function order(array $columns, int $default, string $direction = 'asc'): array
    {
        $this->validate(['order.0.column' => ['sometimes', Rule::in(array_keys($columns))]]);

        return [$columns[$this->input('order.0.column', $default)], $this->input('order.0.dir', $direction)];
    }

    public function searchPattern(): ?string
    {
        $search = trim($this->input('search.value') ?? '');

        return $search === '' ? null : '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
    }
}
