<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\EmailTemplateGetRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplatePatchRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplatePostRequest;
use Motor\Admin\Http\Resources\EmailTemplateCollection;
use Motor\Admin\Http\Resources\EmailTemplateResource;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;
use Motor\Builder\Http\Requests\Api\GridActionRequest;

/**
 * Class EmailTemplatesController
 */
class EmailTemplatesController extends ApiController
{
    protected string $model = EmailTemplate::class;

    protected string $modelResource = 'email_template';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<EmailTemplateCollection>>
     */
    public function index(EmailTemplateGetRequest $request): EmailTemplateCollection
    {
        if ($request->user()->cannot('viewAny', $this->model)) {
            abort(403);
        }
        $paginator = EmailTemplateService::collection()
            ->getPaginator();

        return new EmailTemplateCollection($paginator)->additional(['message' => 'Email template collection read']);
    }

    /**
     * Create record
     */
    public function store(EmailTemplatePostRequest $request): JsonResponse
    {
        $result = EmailTemplateService::create($request)
            ->getResult();

        return new EmailTemplateResource($result)->additional(['message' => 'Email template created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(EmailTemplate $emailTemplate): EmailTemplateResource
    {
        $result = EmailTemplateService::show($emailTemplate)
            ->getResult();

        return new EmailTemplateResource($result)->additional(['message' => 'Email template read']);
    }

    /**
     * Update record
     */
    public function update(EmailTemplatePatchRequest $request, EmailTemplate $emailTemplate): EmailTemplateResource
    {
        $result = EmailTemplateService::update($emailTemplate, $request)
            ->getResult();

        return new EmailTemplateResource($result)->additional(['message' => 'Email template updated']);
    }

    /**
     * Delete record
     */
    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $result = EmailTemplateService::delete($emailTemplate)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Email template deleted']);
        }

        return response()->json(['message' => 'Problem deleting email template'], 400);
    }

    /**
     * Duplicate record
     */
    public function duplicate(GridActionRequest $request): JsonResponse
    {
        $emailTemplates = EmailTemplate::whereIn('id', collect($request->get('data'))->pluck('id'))->get();
        if ($request->get('all')) {
            $emailTemplates = EmailTemplate::get();
        }

        // For loop over all builder Pages and duplicate them
        foreach ($emailTemplates as $emailTemplate) {
            $e = $emailTemplate->replicate();
            $e->name = $e->name.' (Kopie)';
            $e->slug = $e->slug.'_'.Str::uuid()->toString();
            $e->updated_at = Carbon::now();
            $e->created_at = Carbon::now();
            $e->save();
        }

        return response()->json(['message' => 'EmailTemplates duplicated']);
    }

}
