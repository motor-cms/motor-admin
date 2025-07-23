<?php

namespace Motor\Admin\Http\Resources;

class EmailTemplateResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'                      => (int) $this->id,
            'client'                  => new ClientResource($this->client),
            'client_id'               => (int) $this->client_id,
            'language'                => new LanguageResource($this->language),
            'language_id'             => (int) $this->language_id,
            'name'                    => $this->name,
            'slug'                    => $this->slug,
            'subject'                 => $this->subject,
            'body_text'               => $this->body_text,
            'body_html'               => $this->body_html,
            'has_body_html'           => (bool) $this->has_body_html,
            'default_sender_name'     => $this->default_sender_name,
            'default_sender_email'    => $this->default_sender_email,
            'default_recipient_name'  => $this->default_recipient_name,
            'default_recipient_email' => $this->default_recipient_email,
            'default_cc_email'        => $this->default_cc_email,
            'default_bcc_email'       => $this->default_bcc_email,
            'default_replyto_email'   => $this->default_replyto_email,
            'default_replyto_name'    => $this->default_replyto_name,
        ];
    }
}
