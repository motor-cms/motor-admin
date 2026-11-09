<?php

return [
    // Provider of the legacy `ai_help` endpoint (openai, groq, google, anthropic, mistral).
    'provider'    => env('AI_PROVIDER', 'openai'),
    'api_version' => env('AI_API_VERSION'),
    'model'       => env('AI_MODEL'),
    'api_key'     => env('AI_API_KEY'),

    'mistral' => [
        // Only ever set via env/secret, never in a repo file.
        'api_key'  => env('MISTRAL_API_KEY'),
        'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
        'timeout'  => (int) env('MISTRAL_TIMEOUT', 60),
        // Pinned versions instead of -latest aliases, so an alias move never changes output unnoticed.
        'models' => [
            'help'    => env('MISTRAL_MODEL_HELP', 'mistral-small-2603'),
            'builder' => env('MISTRAL_MODEL_BUILDER', 'mistral-medium-2604'),
        ],
        // Only sent to models that reason by default (Large 4 burns the whole budget otherwise).
        'reasoning_effort' => [
            'mistral-large-4' => 'low',
        ],
    ],

    'builder' => [
        'max_repair_rounds'   => (int) env('AI_BUILDER_MAX_REPAIR_ROUNDS', 2),
        'max_sections'        => 6,
        'max_output_tokens'   => (int) env('AI_BUILDER_MAX_OUTPUT_TOKENS', 6000),
        'rate_per_user_min'   => (int) env('AI_BUILDER_RATE_PER_USER_MIN', 10),
        'rate_per_client_day' => (int) env('AI_BUILDER_RATE_PER_CLIENT_DAY', 200),
        // Real third parties the builder never writes about (competitors, comparison portals). The tenant's own
        // name is always allowed. Extend per installation if needed.
        'third_party_names' => [
            'Vattenfall', 'E.ON', 'EON', 'EnBW', 'RWE', 'Uniper', 'EWE', 'Innogy', 'Eprimo', 'Yello', 'LichtBlick',
            'Naturstrom', 'Tibber', 'Octopus Energy', 'Ostrom', 'Polarstern', 'Mainova', 'Thüga', 'Entega',
            'Süwag', 'Pfalzwerke', 'Stadtwerke Saarbrücken', 'Check24', 'Verivox', 'Grünwelt', 'Stromio',
            'Teldafax', 'Flexstrom',
        ],
    ],

    // Images for the builder: own media library first, then stock (only with a key), then generation on request.
    'images' => [
        // Mistral built-in tool image_generation via the Conversations API; the agent model writes the tool prompt.
        'generate'            => (bool) env('AI_IMAGES_GENERATE', true),
        'model'               => env('MISTRAL_MODEL_IMAGE', 'mistral-medium-2604'),
        'timeout'             => (int) env('MISTRAL_IMAGE_TIMEOUT', 120),
        'rate_per_user_min'   => (int) env('AI_IMAGES_RATE_PER_USER_MIN', 5),
        'rate_per_client_day' => (int) env('AI_IMAGES_RATE_PER_CLIENT_DAY', 20),
        // USD per generated image on top of the tokens. Not on Mistral's price page (11/2026); third-party sources
        // say 100 USD per 1,000 images. Check against the console invoice.
        'price_per_image_usd' => (float) env('AI_IMAGES_PRICE_USD', 0.10),
        // The tool answers with a signed download URL; only these hosts are fetched.
        'download_hosts' => ['.blob.core.windows.net', '.mistral.ai'],
        'max_bytes'      => 15 * 1024 * 1024,
        // Stock providers stay off until a key is set. Unsplash is not offered: its API rules require hotlinking,
        // we copy into the media library.
        'stock' => [
            'pexels' => [
                'api_key'  => env('PEXELS_API_KEY'),
                'base_url' => env('PEXELS_BASE_URL', 'https://api.pexels.com/v1'),
            ],
            'pixabay' => [
                'api_key'  => env('PIXABAY_API_KEY'),
                'base_url' => env('PIXABAY_BASE_URL', 'https://pixabay.com/api/'),
            ],
        ],
    ],

    // Store prompt text in ai_requests (off by default: metadata only, plus a hash).
    'log_prompts' => (bool) env('AI_LOG_PROMPTS', false),

    // USD per 1M tokens [input, output] for the cost column in ai_requests.
    // Values from the Mistral price list as of 11/2026 (Medium 3.5 from a third-party source), check before billing.
    'pricing' => [
        'mistral-large-2512'  => [0.5, 1.5],
        'mistral-large-4'     => [0.7, 2.1],
        'mistral-medium-2604' => [1.5, 7.5],
        'mistral-small-2603'  => [0.15, 0.6],
    ],
];
