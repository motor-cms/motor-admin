<?php

namespace Motor\Admin\Services\AI\Builder;

/**
 * Builds the strict JSON schema for Mistral's `response_format` from the
 * catalog manifest the admin sends along.
 *
 * Manifest (v1): { version, client, hash, blocks: [ { id, name, description?,
 * category?, fields: [Field], lists: [ { key, label?, min, max, fields: [Field] } ],
 * media: [ { slot, label? } ] } ] }, Field = { key, label?, type, maxLength?,
 * required?, options? }.
 *
 * Field types beyond the scalars: `image` = { query, alt } (search term for the
 * media library plus alt text), only in item/slot positions; fields may carry a
 * `role` (overline, headline, paragraph, button, caption, image) for the prompt.
 *
 * Intermediate output: { sections: [ { block, fields, lists, media } ], placeholders, refusal }.
 * With content suggestions on, every media entry also carries `alt`.
 * The model never sees uuids, classes or the PageDefinition tree; the admin maps
 * the result through the block factory.
 */
class SectionSchema
{
    public const TEXT_TYPES = ['text', 'textarea', 'rich', 'link'];

    public const PLACEHOLDER_MAX = 20;

    public const MEDIA_QUERY_MAX = 80;

    public const ALT_MAX = 160;

    public const FIELD_TYPES = ['text', 'textarea', 'rich', 'link', 'enum', 'boolean', 'number', 'image'];

    /**
     * Strict decoding cuts a string hard at its maxLength (a paragraph ended in "…und sichen"). The schema
     * therefore allows some headroom; the validator still enforces the real limit and asks for a shorter
     * text in a repair round instead of returning a sentence cut off mid-word.
     */
    public const SCHEMA_HEADROOM = 1.5;

    /** Revision operations (see forRevision) */
    public const REVISION_OPS = ['keep', 'move', 'update', 'delete', 'add'];

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    public static function forManifest(array $manifest, int $maxSections, bool $content = false): array
    {
        $variants = array_map(fn (array $block) => self::blockSchema($block, $content), $manifest['blocks'] ?? []);

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['sections', 'placeholders', 'refusal'],
            'properties'           => [
                // Empty only together with a refusal; the validator asks for at least one section otherwise
                'sections' => [
                    'type'     => 'array',
                    'minItems' => 0,
                    'maxItems' => $maxSections,
                    'items'    => count($variants) === 1 ? $variants[0] : ['anyOf' => $variants],
                ],
                'placeholders' => [
                    'type'     => 'array',
                    'maxItems' => self::PLACEHOLDER_MAX,
                    'items'    => ['type' => 'string', 'maxLength' => 120],
                ],
                // Why the request is declined (third parties, insults); empty otherwise
                'refusal' => ['type' => 'string', 'maxLength' => 300],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    public static function blockSchema(array $block, bool $content = false): array
    {
        $lists = [];
        foreach ($block['lists'] ?? [] as $list) {
            $lists[$list['key']] = [
                'type'     => 'array',
                'minItems' => (int) ($list['min'] ?? 0),
                'maxItems' => (int) ($list['max'] ?? 12),
                'items'    => self::objectOf($list['fields'] ?? []),
            ];
        }

        $slots = array_values(array_map(fn ($m) => (string) $m['slot'], $block['media'] ?? []));

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['block', 'fields', 'lists', 'media'],
            'properties'           => [
                'block'  => ['type' => 'string', 'enum' => [(string) $block['id']]],
                'fields' => self::objectOf($block['fields'] ?? []),
                'lists'  => [
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'required'             => array_keys($lists),
                    'properties'           => (object) $lists,
                ],
                'media' => $slots === []
                    ? ['type' => 'array', 'maxItems' => 0, 'items' => ['type' => 'object']]
                    : [
                        'type'     => 'array',
                        'maxItems' => count($slots),
                        'items'    => [
                            'type'                 => 'object',
                            'additionalProperties' => false,
                            'required'             => $content ? ['slot', 'query', 'alt'] : ['slot', 'query'],
                            'properties'           => array_filter([
                                'slot'  => ['type' => 'string', 'enum' => $slots],
                                'query' => ['type' => 'string', 'maxLength' => self::MEDIA_QUERY_MAX],
                                'alt'   => $content ? ['type' => 'string', 'maxLength' => self::ALT_MAX] : null,
                            ]),
                        ],
                    ],
            ],
        ];
    }

    /**
     * Every field is listed as required (strict mode), optional ones may stay empty.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected static function objectOf(array $fields): array
    {
        $properties = [];
        foreach ($fields as $field) {
            $properties[$field['key']] = self::fieldSchema($field);
        }

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => array_keys($properties),
            'properties'           => (object) $properties,
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    public static function fieldSchema(array $field): array
    {
        return match ($field['type'] ?? 'text') {
            'boolean' => ['type' => 'boolean'],
            'number'  => ['type' => 'number'],
            'image'   => [
                'type'                 => 'object',
                'additionalProperties' => false,
                'required'             => ['query', 'alt'],
                'properties'           => [
                    'query' => ['type' => 'string', 'maxLength' => self::MEDIA_QUERY_MAX],
                    'alt'   => ['type' => 'string', 'maxLength' => self::ALT_MAX],
                ],
            ],
            'enum'    => ($field['options'] ?? []) === []
                ? ['type' => 'string']
                : ['type' => 'string', 'enum' => array_values(array_map('strval', $field['options']))],
            default => array_filter([
                'type'      => 'string',
                'maxLength' => isset($field['maxLength']) ? (int) ceil((int) $field['maxLength'] * self::SCHEMA_HEADROOM) : null,
            ], fn ($v) => $v !== null),
        };
    }

    /**
     * Revision of existing sections ("Mit KI überarbeiten"). Output: { operations: [Op], placeholders, refusal },
     * operations in the new page order:
     * - { op: keep|move|delete, ref } for an existing section (keep = unchanged in place, move = unchanged, new place)
     * - { op: update, ref, section } complete new content of an existing, editable section
     * - { op: add, ref: "neu1", section } new section at this position (page scope only)
     * Scope "section": exactly one update of the target ref.
     *
     * @param  array<string, mixed>  $manifest
     * @param  string[]  $refs  all refs of the context, in page order
     * @param  string[]  $editable  refs the model may change (not locked, block in the catalog)
     * @return array<string, mixed>
     */
    public static function forRevision(array $manifest, array $refs, array $editable, string $scope, ?string $target, int $maxNew): array
    {
        $variants = array_map(fn (array $block) => self::blockSchema($block, true), $manifest['blocks'] ?? []);
        $section = count($variants) === 1 ? $variants[0] : ['anyOf' => $variants];
        $op = fn (array $ops, array $refEnum, bool $withSection) => [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => $withSection ? ['op', 'ref', 'section'] : ['op', 'ref'],
            'properties'           => array_filter([
                'op'      => ['type' => 'string', 'enum' => $ops],
                'ref'     => $refEnum === [] ? ['type' => 'string', 'maxLength' => 20] : ['type' => 'string', 'enum' => array_values($refEnum)],
                'section' => $withSection ? $section : null,
            ]),
        ];

        if ($scope === 'section') {
            $items = $op(['update'], [(string) $target], true);
            $min = 1;
            $max = 1;
        } else {
            $variantsOps = [$op(['keep', 'move', 'delete'], $refs, false)];
            if ($editable !== []) {
                $variantsOps[] = $op(['update'], $editable, true);
            }
            if ($maxNew > 0) {
                $variantsOps[] = $op(['add'], [], true);
            }
            $items = count($variantsOps) === 1 ? $variantsOps[0] : ['anyOf' => $variantsOps];
            $min = 1;
            $max = count($refs) + $maxNew;
        }

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['operations', 'placeholders', 'refusal'],
            'properties'           => [
                'operations' => [
                    'type'     => 'array',
                    'minItems' => $min,
                    'maxItems' => $max,
                    'items'    => $items,
                ],
                'placeholders' => [
                    'type'     => 'array',
                    'maxItems' => self::PLACEHOLDER_MAX,
                    'items'    => ['type' => 'string', 'maxLength' => 120],
                ],
                'refusal' => ['type' => 'string', 'maxLength' => 300],
            ],
        ];
    }
}
