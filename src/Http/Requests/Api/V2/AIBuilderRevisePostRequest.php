<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Request;

/**
 * "Mit KI überarbeiten": the requested change, the existing sections in manifest format (stable refs, no
 * uuids or classes) and the catalog manifest of the admin. Scope "section" revises one section (target),
 * scope "page" may change, reorder, delete and add sections. Authorization happens in the controller.
 */
class AIBuilderRevisePostRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'                    => ['required', 'integer'],
            'prompt'                       => ['required', 'string', 'min:3', 'max:2000'],
            'scope'                        => ['required', 'in:section,page'],
            'target'                       => ['required_if:scope,section', 'nullable', 'string', 'max:20'],
            'max_new'                      => ['sometimes', 'integer', 'min:0', 'max:'.(int) config('ai.builder.max_sections', 6)],
            'sections'                     => ['required', 'array', 'min:1', 'max:40'],
            'sections.*.ref'               => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'sections.*.block'             => ['nullable', 'string', 'max:120'],
            'sections.*.label'             => ['nullable', 'string', 'max:200'],
            'sections.*.locked'            => ['sometimes', 'boolean'],
            'sections.*.fields'            => ['sometimes', 'array'],
            'sections.*.lists'             => ['sometimes', 'array'],
            'sections.*.media'             => ['sometimes', 'array', 'max:10'],
            'context'                      => ['sometimes', 'array'],
            'context.page_title'           => ['sometimes', 'nullable', 'string', 'max:200'],
            'context.page_outline'         => ['sometimes', 'array', 'max:40'],
            'context.page_outline.*'       => ['string', 'max:200'],
            'context.link_targets'         => ['sometimes', 'array', 'max:100'],
            'context.link_targets.*.title' => ['nullable', 'string', 'max:200'],
            'context.link_targets.*.url'   => ['required', 'string', 'max:300'],
            // Attached document (Markdown from ai/builder/document), sent again with every prompt
            'document'          => ['sometimes', 'nullable', 'array'],
            'document.name'     => ['sometimes', 'nullable', 'string', 'max:200'],
            'document.markdown' => ['required_with:document', 'string', 'max:'.((int) config('ai.documents.max_chars', 60000) + 1000)],
            ...AIBuilderSectionPostRequest::manifestRules(),
        ];
    }
}
