<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Motor\Admin\Http\Requests\Api\V2\EmailTemplateGetRequest;
use Motor\Admin\Http\Requests\Api\V2\EmailTemplatePatchRequest;
use Motor\Admin\Http\Requests\Api\V2\EmailTemplatePostRequest;
use Motor\Admin\Http\Resources\V2\EmailTemplateCollection;
use Motor\Admin\Http\Resources\V2\EmailTemplateResource;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;
use Motor\Builder\Http\Requests\Api\V2\GridActionRequest;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Email Templates
 */
class EmailTemplatesController extends ApiController
{
    protected string $model = EmailTemplate::class;

    protected string $modelResource = 'email_template';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<EmailTemplateResource>>
     */
    public function index(EmailTemplateGetRequest $request): EmailTemplateCollection
    {
        if ($request->user()->cannot('viewAny', EmailTemplate::class)) {
            abort(403);
        }

        $paginator = EmailTemplateService::collection()
            ->getPaginator();

        return (new EmailTemplateCollection($paginator))
            ->additional(['meta' => ['message' => 'Email templates retrieved']]);
    }

    public function show(EmailTemplate $emailTemplate): EmailTemplateResource
    {
        $result = EmailTemplateService::show($emailTemplate)
            ->getResult();

        return (new EmailTemplateResource($result))
            ->additional(['meta' => ['message' => 'Email template retrieved']]);
    }

    public function store(EmailTemplatePostRequest $request): JsonResponse
    {
        $result = EmailTemplateService::create($request)
            ->getResult();

        return (new EmailTemplateResource($result))
            ->additional(['meta' => ['message' => 'Email template created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(EmailTemplatePatchRequest $request, EmailTemplate $emailTemplate): EmailTemplateResource
    {
        $result = EmailTemplateService::update($emailTemplate, $request)
            ->getResult();

        return (new EmailTemplateResource($result))
            ->additional(['meta' => ['message' => 'Email template updated']]);
    }

    public function destroy(EmailTemplate $emailTemplate): Response
    {
        if (in_array($emailTemplate->slug, config('motor-admin.protected_email_template_slugs', []), true)) {
            abort(403, 'This email template is protected and cannot be deleted.');
        }

        EmailTemplateService::delete($emailTemplate);

        return $this->noContentResponse();
    }

    /**
     * Duplicate email templates
     *
     * Creates copies of the specified email templates.
     *
     * @response array{meta: array{api_version: string, message: string}}
     */
    public function duplicate(GridActionRequest $request): JsonResponse
    {
        $emailTemplates = EmailTemplate::whereIn('id', collect($request->get('data'))->pluck('id'))->get();
        if ($request->get('all')) {
            $emailTemplates = EmailTemplate::get();
        }

        foreach ($emailTemplates as $emailTemplate) {
            $e = $emailTemplate->replicate();
            $e->name = $e->name.' (Kopie)';
            $e->slug = $e->slug.'_'.Str::uuid()->toString();
            $e->updated_at = Carbon::now();
            $e->created_at = Carbon::now();
            $e->save();
        }

        return response()->json([
            'meta' => [
                'api_version' => 'v2',
                'message' => 'Email templates duplicated',
            ],
        ]);
    }
}
