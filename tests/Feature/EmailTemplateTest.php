<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;

pest()->group('EmailTemplate')->use(RefreshDatabase::class);

describe('EmailTemplate', function () {
    it('can create a EmailTemplate', fn () => assertCrudCreate(
        '/api/email_templates',
        [
            'client_id' => Client::first()->id,
            'language_id' => Language::first()->id,
            'name' => 'test',
            'subject' => 'subject',
        ],
        EmailTemplate::class
    ));

    it("can't create an EmailTemplate with an invalid client", fn () => assertCrudValidation(
        '/api/email_templates',
        [
            'client_id' => 0,
            'language_id' => Language::first()->id,
            'name' => 'test',
            'subject' => 'subject',
        ],
        EmailTemplate::class
    ));

    it("can't create an EmailTemplate with an invalid language", fn () => assertCrudValidation(
        '/api/email_templates',
        [
            'client_id' => Client::first()->id,
            'language_id' => 0,
            'name' => 'test',
            'subject' => 'subject',
        ],
        EmailTemplate::class
    ));

    it("can't create an empty EmailTemplate", fn () => assertCrudValidation(
        '/api/email_templates',
        [],
        EmailTemplate::class
    ));

    it('can get all EmailTemplates', fn () => assertCrudIndex(
        '/api/email_templates',
        1,
        ['id', 'name', 'client']
    ));

    it('can get a specific EmailTemplate', fn () => assertCrudShow(
        '/api/email_templates/'.EmailTemplate::whereName('Error-Template')->first()->id,
        [
            'id', 'client', 'client_id', 'language', 'language_id', 'name', 'slug', 'subject',
            'body_text', 'body_html', 'has_body_html', 'default_sender_name', 'default_sender_email',
            'default_recipient_name', 'default_recipient_email', 'default_cc_email', 'default_bcc_email',
            'default_replyto_email', 'default_replyto_name', 'created_at', 'updated_at',
        ]
    ));

    it('can update emailTemplates', fn () => assertCrudUpdate(
        '/api/email_templates/'.EmailTemplate::whereName('Error-Template')->first()->id,
        [
            'client_id' => Client::first()->id,
            'language_id' => Language::first()->id,
            'name' => 'changed',
            'subject' => 'subject',
        ],
        'name',
        'changed'
    ));

    it('can delete emailTemplates', fn () => assertCrudDelete(
        '/api/email_templates/'.EmailTemplate::whereName('Error-Template')->first()->id,
        EmailTemplate::class
    ));
});
