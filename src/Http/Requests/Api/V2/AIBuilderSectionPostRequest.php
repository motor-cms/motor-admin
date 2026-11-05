<?php

namespace Motor\Admin\Http\Requests\Api\V2;

use Motor\Admin\Http\Requests\Request;

/**
 * Prompt plus the catalog manifest of the admin that sends it (always matches the deployed admin).
 * Authorization (tenant switch, tenant access, builder-pages.ai) happens in the controller.
 */
class AIBuilderSectionPostRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'                                       => ['required', 'integer'],
            'prompt'                                          => ['required', 'string', 'min:3', 'max:2000'],
            'max_sections'                                    => ['sometimes', 'integer', 'min:1', 'max:'.(int) config('ai.builder.max_sections', 6)],
            'context'                                         => ['sometimes', 'array'],
            'context.page_title'                              => ['sometimes', 'nullable', 'string', 'max:200'],
            'context.page_outline'                            => ['sometimes', 'array', 'max:40'],
            'context.page_outline.*'                          => ['string', 'max:200'],
            'context.tone'                                    => ['sometimes', 'nullable', 'string', 'max:200'],
            'catalog_manifest'                                => ['required', 'array'],
            'catalog_manifest.version'                        => ['required', 'integer', 'in:1'],
            'catalog_manifest.blocks'                         => ['required', 'array', 'min:1', 'max:80'],
            'catalog_manifest.blocks.*.id'                    => ['required', 'string', 'max:120'],
            'catalog_manifest.blocks.*.name'                  => ['required', 'string', 'max:200'],
            'catalog_manifest.blocks.*.fields'                => ['present', 'array', 'max:40'],
            'catalog_manifest.blocks.*.fields.*.key'          => ['required', 'string', 'max:120'],
            'catalog_manifest.blocks.*.fields.*.type'         => ['required', 'in:text,textarea,rich,link,enum,boolean,number'],
            'catalog_manifest.blocks.*.lists'                 => ['sometimes', 'array', 'max:10'],
            'catalog_manifest.blocks.*.lists.*.key'           => ['required', 'string', 'max:120'],
            'catalog_manifest.blocks.*.lists.*.min'           => ['required', 'integer', 'min:0'],
            'catalog_manifest.blocks.*.lists.*.max'           => ['required', 'integer', 'min:1', 'max:24'],
            'catalog_manifest.blocks.*.lists.*.fields'        => ['required', 'array', 'max:20'],
            'catalog_manifest.blocks.*.lists.*.fields.*.key'  => ['required', 'string', 'max:120'],
            'catalog_manifest.blocks.*.lists.*.fields.*.type' => ['required', 'in:text,textarea,rich,link,enum,boolean,number'],
            'catalog_manifest.blocks.*.media'                 => ['sometimes', 'array', 'max:10'],
            'catalog_manifest.blocks.*.media.*.slot'          => ['required', 'string', 'max:120'],
        ];
    }
}
