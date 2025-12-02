<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Motor\Admin\Http\Requests\Api\EmailTemplateGetRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplatePatchRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplatePostRequest;
use Motor\Admin\Http\Resources\V2\EmailTemplateCollection;
use Motor\Admin\Http\Resources\V2\EmailTemplateResource;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;
use Motor\Builder\Http\Requests\Api\GridActionRequest;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Email Templates
 */
class EmailTemplatesController extends ApiController
{
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
        EmailTemplateService::delete($emailTemplate);

        return $this->noContentResponse();
    }

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
