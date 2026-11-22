<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Admin\Models\UkAddress;

class UpdateHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('edit-hotels', 'admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('postcode')) {
            $normalized = UkAddress::normalizePostcode((string) $this->input('postcode'));
            $this->merge([
                'postcode' => $normalized ?? strtoupper(trim((string) $this->input('postcode'))),
            ]);
        }

        $this->merge([
            'country' => trim((string) ($this->input('country') ?: 'United Kingdom')),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : false,
            'is_demo' => $this->boolean('is_demo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'county' => ['nullable', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:12', 'regex:' . UkAddress::UK_POSTCODE_REGEX],
            'country' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_demo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postcode.regex' => 'Please enter a valid UK postcode (e.g. E1 1JG).',
        ];
    }
}
