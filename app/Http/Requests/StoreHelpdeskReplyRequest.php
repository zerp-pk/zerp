<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpdeskReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string',
            'is_internal' => 'boolean',
        ];
    }
}