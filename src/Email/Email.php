<?php

namespace Motor\Admin\Email;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;
use Motor\Admin\Models\EmailTemplate;

class Email extends Mailable
{
    protected string $contentHtml = '';

    protected string $contentText = '';

    public function __construct(
        public EmailTemplate $emailTemplate,
        public array $requestData = [],
    ) {
    }

    public function envelope(): Envelope
    {
        // Set sender from email template or request data
        $senderName  = $this->requestData['sender_name'] ?? $this->emailTemplate['default_sender_name'];
        $senderEmail = $this->requestData['sender_email'] ?? $this->emailTemplate['default_sender_email'];

        // Set subject from email template or request data
        $subject     = $this->requestData['subject'] ?? $this->emailTemplate['subject'];

        // Set recipient from email template or request data
        $toName  = $this->requestData['recipient_name'] ?? $this->emailTemplate['default_recipient_name'];
        $toEmail = $this->requestData['recipient_email'] ?? $this->emailTemplate['default_recipient_email'];

        // Set replyto from email template or request data
        $replyToName  = $this->requestData['replyto_name'] ?? $this->emailTemplate['default_replyto_name'] ?? '';
        $replyToEmail = $this->requestData['replyto_email'] ?? $this->emailTemplate['default_replyto_email'] ?? '';

        return new Envelope(
            from: new Address($senderEmail, $senderName),
            to: [new Address($toEmail, $toName)],
            cc: $this->buildAddressArray($this->requestData['cc_email'] ?? $this->emailTemplate['default_cc_email'] ?? ''),
            bcc: $this->buildAddressArray($this->requestData['bcc_email'] ?? $this->emailTemplate['default_bcc_email'] ?? ''),
            replyTo: [new Address($replyToEmail, $replyToName)],
            subject: $subject,
        );
    }

    /**
     * Get the message envelope.
     */
    protected function content(): Content
    {
        Log::info('EmailTemplateSendPostRequest: ' . json_encode($this->requestData));
        $this->contentHtml = $this->requestData['body_html'] ?? '';

        if ($this->emailTemplate['has_body_html'] && !empty($this->emailTemplate['body_html'])) {
            // Set text from email template
            $this->contentHtml = $this->emailTemplate['body_html'];
        }

        Log::info("EmailTemplateSendPostRequest: contentHTML = " . $this->contentHtml);

        $this->contentText = $this->requestData['body_text'] ?? $this->emailTemplate['body_text'] ?? '';

        // Replace placeholders like {FOO}
        if (isset($this->requestData['text_replace_data']) && $this->requestData['text_replace_data']) {

            $this->contentHtml = str_replace('{ALLE_FORMULARFELDER}', implode("<br/>\n", $this->requestData['text_replace_data']), $this->contentHtml);
            $this->contentText = str_replace('{ALLE_FORMULARFELDER}', implode("\n", $this->requestData['text_replace_data']), $this->contentText);

            foreach ($this->requestData['text_replace_data'] as $key => $value) {
                $this->contentHtml = str_replace('{'.strtoupper($key).'}', $value, $this->contentHtml);
                $this->contentText = str_replace('{'.strtoupper($key).'}', $value, $this->contentText);
            }
        }

        return new Content(view: $this->emailTemplate->has_body_html ? 'motor-admin::emails.html_template' : false, text: 'motor-admin::emails.plaintext_template', with: [
            'contentHtml' => $this->emailTemplate->has_body_html ? $this->contentHtml : '',
            'contentText' => $this->contentText,
        ]);
    }

    protected function buildAddressArray(string|null $givenAddresses): array|null
    {
        // If no addresses are given, return null
        if (empty($givenAddresses) || is_null($givenAddresses)) {
            return null;
        }

        // Split on comma, semicolon or space
        $givenAddresses = preg_split("/[\s,;]+/", $givenAddresses);

        // If no addresses are given, return null
        if (count($givenAddresses) == 0) {
            return null;
        }

        // Build address objects
        $addresses = [];
        foreach ($givenAddresses as $address) {
            $addresses[] = new Address($address);
        }

        return $addresses;
    }
}
