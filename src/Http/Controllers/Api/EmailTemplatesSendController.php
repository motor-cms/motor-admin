<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Motor\Admin\Email\Email;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\EmailTemplateSendPostRequest;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;

/**
 * Class EmailTemplatesController
 */
class EmailTemplatesSendController extends ApiController
{
    protected string $model = EmailTemplate::class;

    protected string $modelResource = 'email_template';

    /**
     * Send email with template
     */
    public function send(EmailTemplateSendPostRequest $request, EmailTemplateService $emailTemplateService): JsonResponse
    {
        $emailTemplate = EmailTemplate::where('slug', $request->get('slug'))
            ->where('language_id', $request->get('language_id'))
            ->whereNull('deleted_by')
            ->first();
        // TODO: removed client filter cause template could not be found cause it was not found for client, cause the slugs must be unique in mysql
        // we need to migrate the DB to non unique slugs and handle the client_id slug uniqueness over the request
        // client->where('client_id', $request->get('client_id'))

        if (! is_null($emailTemplate)) {
            $data = Mail::send(new Email($emailTemplate, $request->all()));
            Log::info('Email sent with template', ['template' => $emailTemplate, 'returnData' => $data]);

            return new JsonResponse($emailTemplate);
        }

        return new JsonResponse([
            'message' => 'Email template not found',
        ], 404);
    }
}
