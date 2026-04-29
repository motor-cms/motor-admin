<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;

pest()->group('V2EmailTemplate')->use(RefreshDatabase::class);

describe('V2 EmailTemplate API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/email-templates');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all email templates', function () {
        assertV2CrudIndex('/api/v2/email-templates', 1, ['id', 'name', 'client']);
    });

    it('can get a specific email template', function () {
        assertV2CrudShow(
            '/api/v2/email-templates/'.EmailTemplate::whereName('Error-Template')->first()->id,
            ['id', 'name', 'subject', 'client_id', 'language_id']
        );
    });

    it('can create an email template', function () {
        assertV2CrudCreate('/api/v2/email-templates', [
            'client_id' => Client::first()->id,
            'language_id' => Language::first()->id,
            'name' => 'V2 Test Template',
            'subject' => 'V2 Test Subject',
        ], EmailTemplate::class);
    });

    it('validates required fields on create', function () {
        $countBefore = EmailTemplate::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/email-templates', [])
            ->assertStatus(422);
        expect(EmailTemplate::count() - $countBefore)->toBe(0);
    });

    it("can't create an email template with invalid client", function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/email-templates', [
                'client_id' => 0,
                'language_id' => Language::first()->id,
                'name' => 'Invalid Client Template',
                'subject' => 'Subject',
            ])
            ->assertStatus(422);
    });

    it("can't create an email template with invalid language", function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/email-templates', [
                'client_id' => Client::first()->id,
                'language_id' => 0,
                'name' => 'Invalid Language Template',
                'subject' => 'Subject',
            ])
            ->assertStatus(422);
    });

    it('can update an email template', function () {
        assertV2CrudUpdate(
            '/api/v2/email-templates/'.EmailTemplate::whereName('Error-Template')->first()->id,
            [
                'client_id' => Client::first()->id,
                'language_id' => Language::first()->id,
                'name' => 'V2 Updated Template',
                'subject' => 'V2 Updated Subject',
            ],
            'name',
            'V2 Updated Template'
        );
    });

    it('can delete an email template with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/email-templates/'.EmailTemplate::whereName('Error-Template')->first()->id,
            EmailTemplate::class
        );
    });

    it('can duplicate email templates', function () {
        $countBefore = EmailTemplate::count();
        $template = EmailTemplate::first();

        $response = $this->asAdmin()
            ->postJson('/api/v2/email-templates/duplicate', [
                'action' => 'duplicate',
                'data' => [['id' => $template->id]],
                'all' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonPath('meta.message', 'Email templates duplicated');
        expect(EmailTemplate::count() - $countBefore)->toBe(1);
    });

    it('denies access to unauthenticated users', function () {
        $this->getJson('/api/v2/email-templates')->assertStatus(401);
        $this->postJson('/api/v2/email-templates', [])->assertStatus(401);
    });

    it('can filter email templates by client_id', function () {
        $client = Client::first();
        $otherClient = Client::factory()->create(['slug' => 'other-client']);

        $matchingTemplate = EmailTemplate::factory()->create([
            'client_id' => $client->id,
            'language_id' => Language::first()->id,
            'name' => 'Matching Template',
        ]);
        $otherTemplate = EmailTemplate::factory()->create([
            'client_id' => $otherClient->id,
            'language_id' => Language::first()->id,
            'name' => 'Other Template',
        ]);

        $response = $this->asAdmin()
            ->getJson('/api/v2/email-templates?client_id='.$client->id);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        expect($returnedIds)->toContain($matchingTemplate->id);
        expect($returnedIds)->not->toContain($otherTemplate->id);
    });

    it('can filter email templates by language_id', function () {
        $german = Language::where('iso_639_1', 'de')->first();
        $english = Language::where('iso_639_1', 'en')->first();

        $germanTemplate = EmailTemplate::factory()->create([
            'client_id' => Client::first()->id,
            'language_id' => $german->id,
            'name' => 'German Template',
        ]);
        $englishTemplate = EmailTemplate::factory()->create([
            'client_id' => Client::first()->id,
            'language_id' => $english->id,
            'name' => 'English Template',
        ]);

        $response = $this->asAdmin()
            ->getJson('/api/v2/email-templates?language_id='.$german->id);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        expect($returnedIds)->toContain($germanTemplate->id);
        expect($returnedIds)->not->toContain($englishTemplate->id);
    });
});
