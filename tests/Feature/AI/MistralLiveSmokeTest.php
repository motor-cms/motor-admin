<?php

use Motor\Admin\Services\AI\Builder\SectionGenerator;

/*
 * Opt-in live call against Mistral (costs a fraction of a cent). Never part of CI:
 *   MISTRAL_LIVE=1 MISTRAL_API_KEY=… ./vendor/bin/pest --group=AIBuilderLive
 * The key only comes from the environment of the shell that runs the test.
 */
pest()->group('AIBuilderLive');

it('generates a valid section with the real API', function () {
    $manifest = json_decode(file_get_contents(__DIR__.'/../../Fixtures/ai/manifest-mini.json'), true);

    $result = app(SectionGenerator::class)->generate(
        prompt: 'FAQ-Abschnitt zum Thema Fernwärme mit 3 Fragen',
        manifest: $manifest,
        maxSections: 1,
    );

    expect($result['data']['sections'])->toHaveCount(1)
        ->and($result['data']['sections'][0]['block'])->toBe('ui-page-faq')
        ->and($result['meta']['usage']['prompt_tokens'])->toBeGreaterThan(0);

    fwrite(STDERR, sprintf(
        "\n[live] model=%s rounds=%d duration=%dms tokens=%d/%d\n",
        $result['meta']['model'],
        $result['meta']['repair_rounds'],
        $result['meta']['duration_ms'],
        $result['meta']['usage']['prompt_tokens'],
        $result['meta']['usage']['completion_tokens'],
    ));
})->skip(fn () => ! env('MISTRAL_LIVE') || ! env('MISTRAL_API_KEY'), 'live Mistral test: set MISTRAL_LIVE=1 and MISTRAL_API_KEY');
