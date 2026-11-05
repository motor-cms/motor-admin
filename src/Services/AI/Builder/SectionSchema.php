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
 * Intermediate output: { sections: [ { block, fields, lists, media } ], placeholders }.
 * The model never sees uuids, classes or the PageDefinition tree; the admin maps
 * the result through the block factory.
 */
class SectionSchema
{
    public const TEXT_TYPES = ['text', 'textarea', 'rich', 'link'];

    public const PLACEHOLDER_MAX = 20;

    public const MEDIA_QUERY_MAX = 80;

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    public static function forManifest(array $manifest, int $maxSections): array
    {
        $variants = array_map(fn (array $block) => self::blockSchema($block), $manifest['blocks'] ?? []);

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['sections', 'placeholders'],
            'properties'           => [
                'sections' => [
                    'type'     => 'array',
                    'minItems' => 1,
                    'maxItems' => $maxSections,
                    'items'    => count($variants) === 1 ? $variants[0] : ['anyOf' => $variants],
                ],
                'placeholders' => [
                    'type'     => 'array',
                    'maxItems' => self::PLACEHOLDER_MAX,
                    'items'    => ['type' => 'string', 'maxLength' => 120],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    public static function blockSchema(array $block): array
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
                            'required'             => ['slot', 'query'],
                            'properties'           => [
                                'slot'  => ['type' => 'string', 'enum' => $slots],
                                'query' => ['type' => 'string', 'maxLength' => self::MEDIA_QUERY_MAX],
                            ],
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
            'enum'    => ($field['options'] ?? []) === []
                ? ['type' => 'string']
                : ['type' => 'string', 'enum' => array_values(array_map('strval', $field['options']))],
            default => array_filter([
                'type'      => 'string',
                'maxLength' => isset($field['maxLength']) ? (int) $field['maxLength'] : null,
            ], fn ($v) => $v !== null),
        };
    }
}
