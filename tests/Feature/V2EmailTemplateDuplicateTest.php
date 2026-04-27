<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\EmailTemplate;

pest()
    ->group('V2EmailTemplateDuplicate')
    ->use(RefreshDatabase::class);

describe('V2 EmailTemplate per-id duplicate', function () {

    it('duplicates a single template via the v2 per-id endpoint', function () {
        $template    = EmailTemplate::first();
        $countBefore = EmailTemplate::count();

        $response = $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson("/api/v2/email-templates/{$template->id}/duplicate");

        $response->assertStatus(201)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('meta.message', 'Email template duplicated');

        expect(EmailTemplate::count() - $countBefore)->toBe(1);

        $newId = $response->json('data.id');
        expect($newId)->not->toBe($template->id);

        $dupe = EmailTemplate::find($newId);
        expect($dupe)->not->toBeNull()
            ->and($dupe->name)->toBe($template->name.' (Kopie)')
            ->and($dupe->slug)->toStartWith($template->slug.'_')
            ->and($dupe->slug)->not->toBe($template->slug)
            ->and($dupe->client_id)->toBe($template->client_id)
            ->and($dupe->subject)->toBe($template->subject)
            ->and($dupe->body_html)->toBe($template->body_html);
    });

    it('returns 404 with v2 envelope for an unknown id', function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v2/email-templates/99999999/duplicate')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('returns 401 when unauthenticated', function () {
        $template = EmailTemplate::first();

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson("/api/v2/email-templates/{$template->id}/duplicate")
            ->assertStatus(401);
    });

    it('does not affect the bulk endpoint', function () {
        $countBefore = EmailTemplate::count();
        $template    = EmailTemplate::first();

        $this->asAdmin()
            ->postJson('/api/v2/email-templates/duplicate', [
                'action' => 'duplicate',
                'data'   => [['id' => $template->id]],
                'all'    => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('meta.message', 'Email templates duplicated');

        expect(EmailTemplate::count() - $countBefore)->toBe(1);
    });
});
