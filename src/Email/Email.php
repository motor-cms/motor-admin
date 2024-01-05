<?php

namespace Motor\Admin\Email;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Motor\Admin\Models\EmailTemplate;

class Email extends Mailable
{
    /**
     * @var string
     */
    protected string $contentHtml = '';

    /**
     * @var string
     */
    protected string $contentText = '';

    /**
     * @param \Motor\Admin\Models\EmailTemplate $emailTemplate
     * @param array $requestData
     */
    public function __construct(
        public EmailTemplate $emailTemplate,
        public array $requestData = [],
    ) {
    }

    /**
     * @return \Illuminate\Mail\Mailables\Envelope
     */
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
        $replyToName  = $this->requestData['replyto_name'] ?? $this->emailTemplate['default_replyto_name'];
        $replyToEmail = $this->requestData['replyto_email'] ?? $this->emailTemplate['default_replyto_email'];

        return new Envelope(
            from: new Address($senderEmail, $senderName),
            to: [ new Address($toEmail, $toName) ],
            cc: $this->buildAddressArray($this->requestData['cc_email'] ?? $this->emailTemplate['default_cc_email']),
            bcc: $this->buildAddressArray($this->requestData['bcc_email'] ?? $this->emailTemplate['default_bcc_email']),
            replyTo: [ new Address($replyToEmail, $replyToName) ],
            subject: $subject,
        );
    }

    /**
     * Get the message envelope.
     */
    protected function content(): Content
    {
        // Set text from email template
        $this->contentHtml = $this->requestData['body_html'] ?? $this->emailTemplate['body_html'];
        $this->contentText = $this->requestData['body_text'] ?? $this->emailTemplate['body_text'];

        // Replace placeholders like {FOO}
        if (isset($this->requestData['text_replace_data']) && $this->requestData['text_replace_data']) {
            foreach ($this->requestData['text_replace_data'] as $key => $value) {
                $this->contentHtml = str_replace('{'.strtoupper($key).'}', $value, $this->contentHtml);
                $this->contentText = str_replace('{'.strtoupper($key).'}', $value, $this->contentText);
            }
        }

        return new Content(view: 'motor-admin::emails.html_template', text: 'motor-admin::emails.plaintext_template', with: [
                'contentHtml' => $this->contentHtml,
                'contentText' => $this->contentText,
            ]);
    }

    /**
     * @param string $givenAddresses
     * @return array|null
     */
    protected function buildAddressArray(string $givenAddresses): array|null
    {
        // If no addresses are given, return null
        if (empty($givenAddresses)) {
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
