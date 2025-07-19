<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name' . ($this->department ? ',' . $this->department->id : '')
            ],
            'description' => 'nullable|string',
            'status' => 'sometimes|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم القسم مطلوب',
            'name.unique' => 'اسم القسم مسجل مسبقاً',
            'status.boolean' => 'حالة القسم يجب أن تكون true أو false'
        ];
    }
}