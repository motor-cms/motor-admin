<?php

namespace Motor\Admin\Services\AI\Images\Stock;

/**
 * Stock providers in the order they are asked; only those with an API key.
 */
class StockProviders
{
    /** @var list<StockImageProvider> */
    protected array $providers;

    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?? [new PexelsProvider, new PixabayProvider];
    }

    /** @return list<StockImageProvider> */
    public function enabled(): array
    {
        return array_values(array_filter($this->providers, fn (StockImageProvider $p) => $p->enabled()));
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_map(fn (StockImageProvider $p) => $p->name(), $this->enabled());
    }

    public function get(string $name): ?StockImageProvider
    {
        foreach ($this->enabled() as $provider) {
            if ($provider->name() === $name) {
                return $provider;
            }
        }

        return null;
    }
}
