<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportHotelCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('import-hotels', 'admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }
}
