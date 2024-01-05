<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Motor\Admin\Email\Email;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Controllers\Controller;
use Motor\Admin\Http\Requests\Api\EmailTemplatePatchRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplatePostRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplateSendPostRequest;
use Motor\Admin\Http\Resources\EmailTemplateCollection;
use Motor\Admin\Http\Resources\EmailTemplateResource;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;

/**
 * Class EmailTemplatesController
 */
class EmailTemplatesSendController extends Controller
{
    protected string $model = EmailTemplate::class;

    protected string $modelResource = 'email_template';

    public function send(EmailTemplateSendPostRequest $request , EmailTemplateService $emailTemplateService): JsonResponse
    {
        $emailTemplate = EmailTemplate::where('slug', $request->get('slug'))
            ->where('client_id', $request->get('client_id'))
            ->where('language_id', $request->get('language_id'))
            ->whereNull('deleted_by')
            ->first();

        if (! is_null($emailTemplate)) {
            Mail::send(new Email($emailTemplate, $request->all()));
            return new JsonResponse($emailTemplate);
        }

        return new JsonResponse([
            'message' => 'Email template not found'
        ], 404);
    }
}
