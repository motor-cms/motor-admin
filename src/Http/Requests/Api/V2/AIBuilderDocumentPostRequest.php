<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Request;

/**
 * "Dokument anhängen" in the KI dialog: one PDF, Word or PowerPoint file. Authorization (tenant switch, tenant
 * access, ai.use) and the budget check happen in the controller.
 */
class AIBuilderDocumentPostRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer'],
            'file'      => [
                'required',
                'file',
                'max:'.(int) config('ai.documents.max_kb', 10240),
                'mimes:'.implode(',', (array) config('ai.documents.mimes', ['pdf', 'docx', 'pptx'])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Bitte eine PDF-, Word- (.docx) oder PowerPoint-Datei (.pptx) wählen.',
            'file.max'   => 'Die Datei ist zu groß (höchstens '.(int) round(config('ai.documents.max_kb', 10240) / 1024).' MB).',
        ];
    }
}
