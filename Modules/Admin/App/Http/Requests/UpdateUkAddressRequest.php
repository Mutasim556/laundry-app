<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Admin\Models\UkAddress;

class UpdateUkAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('edit-uk-addresses', 'admin') ?? false;
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
            'postcode' => ['required', 'string', 'max:12', 'regex:' . UkAddress::UK_POSTCODE_REGEX],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'building_name' => ['nullable', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'county' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
            'is_demo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postcode.regex' => 'Please enter a valid UK postcode (e.g. E1 0BJ).',
        ];
    }
}
