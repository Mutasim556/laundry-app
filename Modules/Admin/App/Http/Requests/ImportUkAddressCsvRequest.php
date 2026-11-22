<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUkAddressCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('import-uk-addresses', 'admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.mimes' => 'Only valid CSV files (.csv, .txt) are supported.',
            'csv_file.max' => 'The CSV file must not exceed 10MB.',
        ];
    }
}
