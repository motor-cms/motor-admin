<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;

/**
 * Class EmailTemplatePostRequest
 *
 * @OA\Schema(
 *   schema="EmailTemplatePostRequest",
 *
 *   @OA\Property(
 *     property="client_id",
 *     type="integer",
 *     example="1"
 *   ),
 *   @OA\Property(
 *     property="language_id",
 *     type="integer",
 *     example="2"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     example="My email template"
 *   ),
 *   @OA\Property(
 *     property="subject",
 *     type="string",
 *     example="My email subject"
 *   ),
 *   @OA\Property(
 *     property="body_text",
 *     type="string",
 *     example="The complete email body as text"
 *   ),
 *   @OA\Property(
 *     property="body_html",
 *     type="string",
 *     example="The complete email body with html tags"
 *   ),
 *   @OA\Property(
 *     property="default_sender_name",
 *     type="string",
 *     example="Motor Administrator"
 *   ),
 *   @OA\Property(
 *     property="default_sender_email",
 *     type="string",
 *     example="sender@motor-cms.com"
 *   ),
 *   @OA\Property(
 *     property="default_recipient_name",
 *     type="string",
 *     example="Motor User"
 *   ),
 *   @OA\Property(
 *     property="default_recipient_email",
 *     type="string",
 *     example="recipient@motor-cms.com"
 *   ),
 *   @OA\Property(
 *     property="default_cc_email",
 *     type="string",
 *     description="Comma separated list of email addresses",
 *     example="cc1@motor-cms.com,cc2.motor-cms.com"
 *   ),
 *   @OA\Property(
 *     property="default_bcc_email",
 *     type="string",
 *     description="Comma separated list of email addresses",
 *     example="bcc1@motor-cms.com,bcc2.motor-cms.com"
 *   ),
 *   required={"name", "client_id", "language_id", "subject"},
 * )
 */
class EmailTemplatePostRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id'               => 'required|integer',
            'language_id'             => 'required|integer',
            'name'                    => 'required',
            'subject'                 => 'required',
            'body_text'               => 'nullable',
            'body_html'               => 'nullable',
            'default_sender_name'     => 'nullable',
            'default_sender_email'    => 'nullable|email',
            'default_recipient_name'  => 'nullable',
            'default_recipient_email' => 'nullable|email',
            'default_cc_email'        => 'nullable',
            'default_bcc_email'       => 'nullable',
        ];
    }
}
