<?php

namespace Motor\Admin\Http\Requests\Api;

use Motor\Admin\Http\Requests\Request;
use OpenApi\Annotations as OA;

/**
 * Class EmailTemplateSendPostRequest
 *
 * @OA\Schema(
 *   schema="EmailTemplateSendPostRequest",
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
 *     property="slug",
 *     type="string",
 *     example="my-email-template"
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
 *     property="sender_name",
 *     type="string",
 *     example="Motor Administrator"
 *   ),
 *   @OA\Property(
 *     property="sender_email",
 *     type="string",
 *     example="sender@motor-cms.com"
 *   ),
 *   @OA\Property(
 *     property="recipient_name",
 *     type="string",
 *     example="Motor User"
 *   ),
 *   @OA\Property(
 *     property="recipient_email",
 *     type="string",
 *     example="recipient@motor-cms.com"
 *   ),
 *   @OA\Property(
 *     property="cc_email",
 *     type="string",
 *     description="Comma separated list of email addresses",
 *     example="cc1@motor-cms.com,cc2.motor-cms.com"
 *   ),
 *   @OA\Property(
 *     property="bcc_email",
 *     type="string",
 *     description="Comma separated list of email addresses",
 *     example="bcc1@motor-cms.com,bcc2.motor-cms.com"
 *   ),
 *   @OA\Property(
 *      property="data",
 *      type="array",
 *      description="Key value pairs of data that can be used in the email template",
 *      example="{FOO: 'bar'}",
 *     @OA\Items(
 *     type="string",
 *     example="FOO: 'bar'"
 *    )
 *    ),
 *   required={"slug", "client_id", "language_id"}
 * )
 */
class EmailTemplateSendPostRequest extends Request
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
            'client_id'         => 'required|integer',
            'language_id'       => 'required|integer',
            'slug'              => 'required',
            'subject'           => 'nullable',
            'body_text'         => 'nullable',
            'body_html'         => 'nullable',
            'sender_name'       => 'nullable',
            'sender_email'      => 'nullable|email',
            'recipient_name'    => 'nullable',
            'recipient_email'   => 'nullable|email',
            'cc_email'          => 'nullable',
            'bcc_email'         => 'nullable',
            'replyto_email'     => 'nullable',
            'replyto_name'      => 'nullable',
            'text_replace_data' => 'nullable|array',
        ];
    }
}
