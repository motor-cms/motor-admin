<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Resources\V2\EmailTemplateResource;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * V2 Email Template Duplicate Controller with standardized responses.
 *
 * @tags Email Templates
 */
class EmailTemplateDuplicateController extends ApiController
{
    /**
     * Duplicate an email template
     *
     * Creates a copy of the email template with name suffixed " (Kopie)"
     * and a uuid-suffixed slug to keep it unique.
     *
     * @response 201 EmailTemplateResource
     */
    public function store(EmailTemplate $emailTemplate): JsonResponse
    {
        $result = EmailTemplateService::duplicate($emailTemplate);

        return (new EmailTemplateResource($result))
            ->additional(['meta' => ['message' => 'Email template duplicated']])
            ->response()
            ->setStatusCode(201);
    }
}
