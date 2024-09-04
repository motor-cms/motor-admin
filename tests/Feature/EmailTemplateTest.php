<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;

describe('EmailTemplate', function () {
    it('can create a EmailTemplate', function () {
        $emailTemplatecount = EmailTemplate::count();
        $this->asAdmin()
            ->post('/api/email_templates', [
                'client_id' => Client::first()->id,
                'language_id' => Language::first()->id,
                'name' => 'test',
                'subject' => 'subject',
            ])->assertStatus(201);
        expect(EmailTemplate::count() - $emailTemplatecount)->toBe(1);
    });
    it("can't create an EmailTemplate with an invalid client", function () {
        $emailTemplatecount = EmailTemplate::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/email_templates', [
                'client_id' => 0,
                'language_id' => Language::first()->id,
                'name' => 'test',
                'subject' => 'subject',
            ])->assertStatus(422);
        expect(EmailTemplate::count() - $emailTemplatecount)->toBe(0);
    });
    it("can't create an EmailTemplate with an invalid language", function () {
        $emailTemplatecount = EmailTemplate::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/email_templates', [
                'client_id' => Client::first()->id,
                'language_id' => 0,
                'name' => 'test',
                'subject' => 'subject',
            ])->assertStatus(422);
        expect(EmailTemplate::count() - $emailTemplatecount)->toBe(0);
    });
    it("can't create an empty EmailTemplate", function () {
        $emailTemplatecount = EmailTemplate::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/email_templates', [])->assertStatus(422);
        expect(EmailTemplate::count() - $emailTemplatecount)->toBe(0);
    });
    it('can get all EmailTemplates')
        ->asAdmin()
        ->get('/api/email_templates')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has(
            'data',
            1,
            fn(AssertableJson $data) =>
            $data
                ->has('id')
                ->has('name')
                ->has('client')
                ->etc()
        )->etc());
    it(
        'can get a specific EmailTemplate',
        fn() =>
        $this->asAdmin()->get('/api/email_templates/' . EmailTemplate::whereName('Error-Template')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                fn(AssertableJson $data) =>
                $data
                    ->has('id')
                    ->has('client')
                    ->has('client_id')
                    ->has('language')
                    ->has('language_id')
                    ->has('name')
                    ->has('slug')
                    ->has('subject')
                    ->has('body_text')
                    ->has('body_html')
                    ->has('has_body_html')
                    ->has('default_sender_name')
                    ->has('default_sender_email')
                    ->has('default_recipient_name')
                    ->has('default_recipient_email')
                    ->has('default_cc_email')
                    ->has('default_bcc_email')
                    ->has('default_replyto_email')
                    ->has('default_replyto_name')
            )->etc())
    );
    it('can update emailTemplates', fn() => $this->asAdmin()
        ->put('/api/email_templates/' . EmailTemplate::whereName('Error-Template')->first()->id, [
            'client_id' => Client::first()->id,
            'language_id' => Language::first()->id,
            'name' => 'changed',
            'subject' => 'subject',
        ])->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $data) =>
        $data->where('name', 'changed')->etc())->etc()));
    it('can delete emailTemplates', function () {
        $emailTemplatecount = EmailTemplate::count();
        $this->asAdmin()->delete('/api/email_templates/' . EmailTemplate::whereName('Error-Template')->first()->id)
            ->assertStatus(200);
        expect($emailTemplatecount - EmailTemplate::count())->toBe(1);
    });
});
