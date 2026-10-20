<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body'             => ['required', 'string', 'max:2000'],
            'participant_ids'  => ['nullable', 'array'],
            'participant_ids.*'=> ['integer', 'exists:users,id'],
            'subject'          => ['nullable', 'string', 'max:180'],
            'conversation_id'  => ['nullable', 'integer', 'exists:conversations,id'],
            'maintenance_bill_document_id' => ['nullable', 'integer', 'exists:maintenance_bill_documents,id'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'extensions:pdf,jpg,jpeg,png,webp',
                'max:'.(int) config('communityos.communication.attachment_max_kb', 10240),
            ],
        ];
    }
}
