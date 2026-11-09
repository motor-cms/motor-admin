<?php

namespace Motor\Admin\Services\AI\Images;

/**
 * Machine-readable "AI generated" marking in the file itself (AI Act Art. 50 (2)).
 *
 * Mistral's images already carry a signed C2PA manifest with digitalSourceType trainedAlgorithmicMedia. Its hash
 * binding covers the byte layout of the file, so inserting anything (even an XMP segment) would make the signature
 * fail: such files stay byte for byte as delivered. Files without C2PA get an XMP packet with IPTC
 * Iptc4xmpExt:DigitalSourceType = trainedAlgorithmicMedia (JPEG: APP1 segment, PNG: iTXt chunk XML:com.adobe.xmp
 * plus a tEXt "AI generated" note), written without re-encoding the image. WebP and other formats are left
 * alone; the marking then only lives in the media metadata (origin, ai_labeling).
 */
class AiImageMarker
{
    public const DIGITAL_SOURCE_TYPE = 'http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia';

    /**
     * @return array{bytes: string, marking: 'c2pa'|'xmp'|'none'}
     */
    public static function mark(string $bytes, string $mime, string $creatorTool, string $description = 'AI generated'): array
    {
        if (self::hasC2pa($bytes, $mime)) {
            return ['bytes' => $bytes, 'marking' => 'c2pa'];
        }

        $xmp = self::xmp($creatorTool, $description);

        return match ($mime) {
            'image/jpeg' => ['bytes' => self::jpegWithXmp($bytes, $xmp), 'marking' => 'xmp'],
            'image/png'  => ['bytes' => self::pngWithXmp($bytes, $xmp, $description), 'marking' => 'xmp'],
            default      => ['bytes' => $bytes, 'marking' => 'none'],
        };
    }

    /** JPEG: APP11 JUMBF box labelled c2pa; PNG: caBX chunk. */
    public static function hasC2pa(string $bytes, string $mime): bool
    {
        if ($mime === 'image/png') {
            return str_contains(self::pngChunkTypes($bytes), '|caBX|');
        }
        if ($mime !== 'image/jpeg') {
            return false;
        }
        foreach (self::jpegSegments($bytes) as [$marker, $payload]) {
            if ($marker === 0xEB && str_contains($payload, 'jumb') && str_contains($payload, 'c2pa')) {
                return true;
            }
        }

        return false;
    }

    /** Whether the file says "trained algorithmic media" (C2PA assertion or XMP). */
    public static function isMarked(string $bytes): bool
    {
        return str_contains($bytes, 'trainedAlgorithmicMedia');
    }

    public static function xmp(string $creatorTool, string $description): string
    {
        $esc = fn (string $v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xpacket begin="'."\u{FEFF}".'" id="W5M0MpCehiHzreSzNTczkc9d"?>'
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description rdf:about="" xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/"'
            .' xmlns:xmp="http://ns.adobe.com/xap/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/"'
            .' Iptc4xmpExt:DigitalSourceType="'.self::DIGITAL_SOURCE_TYPE.'" xmp:CreatorTool="'.$esc($creatorTool).'">'
            .'<dc:description><rdf:Alt><rdf:li xml:lang="x-default">'.$esc($description).'</rdf:li></rdf:Alt></dc:description>'
            .'</rdf:Description></rdf:RDF></x:xmpmeta><?xpacket end="w"?>';
    }

    protected static function jpegWithXmp(string $bytes, string $xmp): string
    {
        if (! str_starts_with($bytes, "\xFF\xD8")) {
            return $bytes;
        }
        $payload = "http://ns.adobe.com/xap/1.0/\0".$xmp;
        if (strlen($payload) + 2 > 0xFFFF) {
            return $bytes;
        }
        // After SOI and a JFIF/EXIF APP0/APP1, so readers that expect those first still find them
        $pos = 2;
        foreach (self::jpegSegments($bytes) as [$marker, , $offset, $length]) {
            if ($marker !== 0xE0 && ! ($marker === 0xE1 && ! str_starts_with(substr($bytes, $offset + 4, 29), 'http://ns.adobe.com/xap/1.0/'))) {
                break;
            }
            $pos = $offset + 2 + $length;
        }
        $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($bytes, 0, $pos).$segment.substr($bytes, $pos);
    }

    protected static function pngWithXmp(string $bytes, string $xmp, string $description): string
    {
        $signature = "\x89PNG\r\n\x1A\n";
        if (! str_starts_with($bytes, $signature) || strlen($bytes) < 33) {
            return $bytes;
        }
        // IHDR is always first (8 + 4 + 4 + 13 + 4 bytes)
        $afterIhdr = 8 + 25;
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $itxt = $chunk('iTXt', "XML:com.adobe.xmp\0\0\0\0\0".$xmp);
        $text = $chunk('tEXt', "Description\0".$description);

        return substr($bytes, 0, $afterIhdr).$itxt.$text.substr($bytes, $afterIhdr);
    }

    /**
     * Segments before the image data: [marker, payload, offset, length].
     *
     * @return list<array{0: int, 1: string, 2: int, 3: int}>
     */
    protected static function jpegSegments(string $bytes): array
    {
        $segments = [];
        $pos = 2;
        $size = strlen($bytes);
        while ($pos + 4 <= $size && $bytes[$pos] === "\xFF") {
            $marker = ord($bytes[$pos + 1]);
            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }
            $length = unpack('n', substr($bytes, $pos + 2, 2))[1];
            $segments[] = [$marker, substr($bytes, $pos + 4, max(0, $length - 2)), $pos, $length];
            $pos += 2 + $length;
        }

        return $segments;
    }

    protected static function pngChunkTypes(string $bytes): string
    {
        $types = '|';
        $pos = 8;
        $size = strlen($bytes);
        while ($pos + 8 <= $size) {
            $length = unpack('N', substr($bytes, $pos, 4))[1];
            $type = substr($bytes, $pos + 4, 4);
            $types .= $type.'|';
            if ($type === 'IEND') {
                break;
            }
            $pos += 12 + $length;
        }

        return $types;
    }
}
