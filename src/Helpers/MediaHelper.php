<?php

namespace Motor\Admin\Helpers;

use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;

use function GuzzleHttp\Psr7\mimetype_from_filename;

/**
 * Class MediaHelper
 */
class MediaHelper
{
    public static function getFileInformation(
        HasMedia $record,
        string $identifier,
        bool $base64 = false,
        array $conversions = []
    ): array {
        $data = [];
        $items = $record->getMedia($identifier);

        $host = config('app.url');
        URL::forceRootUrl(config('app.url'));

        // $host = ( isset($_SERVER['HTTPS']) ? "https" : "http" ) . "://".$_SERVER['HTTP_HOST'];

        if (isset($items[0])) {
            $data['file_original'] = url($items[0]->getUrl());
            $data['file_original_relative'] = str_replace($host, '', url($items[0]->getUrl()));
            $data['file_size'] = $items[0]->size;
            $data['name'] = $items[0]->name;
            $data['file_name'] = $items[0]->file_name;
            $data['mime_type'] = mimetype_from_filename($items[0]->file_name);
            $data['is_generating'] = $items[0]->hasCustomProperty('generating');

            if ($base64) {
                $data['file_base64'] = base64_encode(file_get_contents(public_path().urldecode($items[0]->getUrl())));
            }

            foreach ($conversions as $conversion) {
                $data[$conversion] = url($items[0]->getUrl($conversion));
            }
        }

        return $data;
    }
}
