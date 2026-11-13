<?php

namespace Motor\Admin\Services\AI\Documents;

use Illuminate\Http\UploadedFile;
use Motor\Admin\Services\AI\MistralClient;

/**
 * "Dokument anhängen" in the builder's KI dialog: PDF, Word or PowerPoint → Markdown via Mistral OCR.
 *
 * The file goes to Mistral as a base64 data URI and is never written to disk or the media library. Only the first
 * `ai.documents.max_pages` pages are read; the Markdown is cleaned (no embedded image references, collapsed blank
 * lines) and cut at `ai.documents.max_chars`, so one upload can feed several generation prompts.
 */
class DocumentExtractor
{
    /** @var array<string, string> extension => MIME type sent to Mistral */
    public const MIME_TYPES = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public function __construct(protected MistralClient $client) {}

    /**
     * @return array{name: string, pages: int, chars: int, truncated: bool, markdown: string, model: string, usage: array{pages_processed: int, doc_size_bytes: int}}
     *
     * @throws DocumentUnreadableException when the OCR result holds no text (scan without text, empty file)
     */
    public function extract(UploadedFile $file): array
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $mime = self::MIME_TYPES[$extension] ?? 'application/pdf';
        $maxPages = max(1, (int) config('ai.documents.max_pages', 30));

        $result = $this->client->ocr([
            'model'    => (string) config('ai.documents.model', 'mistral-ocr-latest'),
            'document' => [
                'type'         => 'document_url',
                'document_url' => 'data:'.$mime.';base64,'.base64_encode((string) $file->get()),
            ],
            'pages'       => range(0, $maxPages - 1),
            // No embedded images: only text is used (include_image_base64=false is refused for .docx)
            'image_limit' => 0,
        ], (int) config('ai.documents.timeout', 120));

        $pages = collect($result['pages'])
            ->sortBy(fn ($page) => (int) ($page['index'] ?? 0))
            ->map(fn ($page) => self::cleanMarkdown((string) ($page['markdown'] ?? '')))
            ->filter(fn (string $markdown) => $markdown !== '')
            ->values();

        $markdown = $pages->implode("\n\n");
        if (trim(strip_tags($markdown)) === '') {
            throw new DocumentUnreadableException('OCR returned no text');
        }

        $maxChars = max(1000, (int) config('ai.documents.max_chars', 60000));
        $truncated = mb_strlen($markdown) > $maxChars;
        if ($truncated) {
            $markdown = rtrim(mb_substr($markdown, 0, $maxChars));
        }

        $pagesProcessed = (int) ($result['usage']['pages_processed'] ?? count($result['pages']));

        return [
            'name'      => self::displayName($file->getClientOriginalName()),
            'pages'     => $pagesProcessed,
            'chars'     => mb_strlen($markdown),
            'truncated' => $truncated,
            'markdown'  => $markdown,
            'model'     => $result['model'],
            'usage'     => [
                'pages_processed' => $pagesProcessed,
                'doc_size_bytes'  => (int) ($result['usage']['doc_size_bytes'] ?? $file->getSize()),
            ],
        ];
    }

    public static function costUsd(int $pages): float
    {
        return round($pages * (float) config('ai.documents.price_per_page_usd', 0.001), 6);
    }

    /**
     * Image references (`![img-0.jpeg](img-0.jpeg)`) point to nothing we keep; runs of blank lines waste tokens.
     */
    public static function cleanMarkdown(string $markdown): string
    {
        $markdown = (string) preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', $markdown);
        $markdown = (string) preg_replace("/[ \t]+\n/u", "\n", $markdown);
        $markdown = (string) preg_replace("/\n{3,}/u", "\n\n", $markdown);

        return trim($markdown);
    }

    /**
     * File name without path parts or control characters, at most 120 characters.
     */
    public static function displayName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        return mb_substr(trim($name), 0, 120) ?: 'Dokument';
    }
}
