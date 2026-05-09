<?php

use App\Http\Middleware\InternalApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;

pest()->group('EmailTemplateSendAuth')->use(RefreshDatabase::class);

beforeEach(function () {
    config(['services.internal_api.token' => 'test-shared-secret']);
});

describe('email_templates/send authentication', function () {
    it('rejects requests without an X-Internal-Token header', function () {
        $this->postJson('/api/email_templates/send', [
            'client_id' => Client::first()->id,
            'language_id' => Language::first()->id,
            'slug' => 'whatever',
        ])->assertStatus(401);
    });

    it('rejects requests with a wrong X-Internal-Token header', function () {
        $this->withHeader(InternalApiToken::HEADER, 'wrong-token')
            ->postJson('/api/email_templates/send', [
                'client_id' => Client::first()->id,
                'language_id' => Language::first()->id,
                'slug' => 'whatever',
            ])->assertStatus(401);
    });

    it('rejects requests when the configured token is empty', function () {
        config(['services.internal_api.token' => '']);

        $this->withHeader(InternalApiToken::HEADER, '')
            ->postJson('/api/email_templates/send', [
                'client_id' => Client::first()->id,
                'language_id' => Language::first()->id,
                'slug' => 'whatever',
            ])->assertStatus(401);
    });

    it('lets the request through when the X-Internal-Token header matches', function () {
        Mail::fake();

        $template = EmailTemplate::factory()->create([
            'client_id' => Client::first()->id,
            'language_id' => Language::first()->id,
            'slug' => 'auth-passthrough-test',
        ]);

        $this->withHeader(InternalApiToken::HEADER, 'test-shared-secret')
            ->postJson('/api/email_templates/send', [
                'client_id' => $template->client_id,
                'language_id' => $template->language_id,
                'slug' => 'auth-passthrough-test',
            ])->assertStatus(200);
    });
});
