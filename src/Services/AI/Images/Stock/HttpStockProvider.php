<?php

namespace Motor\Admin\Services\AI\Images\Stock;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Services\AI\AIProviderException;

abstract class HttpStockProvider implements StockImageProvider
{
    /** Hosts the image files may come from (no redirects to elsewhere). */
    abstract protected function downloadHosts(): array;

    protected function config(string $key, mixed $default = null): mixed
    {
        return config('ai.images.stock.'.$this->name().'.'.$key, $default);
    }

    public function enabled(): bool
    {
        return filled($this->config('api_key'));
    }

    public function download(StockImage $image): string
    {
        $host = mb_strtolower((string) parse_url($image->downloadUrl, PHP_URL_HOST));
        $allowed = collect($this->downloadHosts())->contains(fn (string $h) => $host === $h || str_ends_with($host, '.'.$h));
        if (parse_url($image->downloadUrl, PHP_URL_SCHEME) !== 'https' || ! $allowed) {
            throw new AIProviderException('Stock image host not allowed.');
        }
        try {
            $response = Http::timeout(60)->withOptions(['allow_redirects' => false])->get($image->downloadUrl);
        } catch (ConnectionException) {
            throw new AIProviderException(ucfirst($this->name()).' is not reachable.');
        }
        if (! $response->successful()) {
            throw new AIProviderException(ucfirst($this->name()).' download returned HTTP '.$response->status().'.');
        }

        return $response->body();
    }
}
