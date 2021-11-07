<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\EmailTemplatePatchRequest;
use Motor\Admin\Http\Requests\Api\EmailTemplatePostRequest;
use Motor\Admin\Http\Resources\EmailTemplateCollection;
use Motor\Admin\Http\Resources\EmailTemplateResource;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Services\EmailTemplateService;

/**
 * Class EmailTemplatesController
 *
 * @package Motor\Admin\Http\Controllers\Api
 */
class EmailTemplatesController extends ApiController
{
    protected string $model = 'Motor\Admin\Models\EmailTemplate';

    protected string $modelResource = 'email_template';

    /**
     * @OA\Get (
     *   tags={"EmailTemplatesController"},
     *   path="/api/email_templates",
     *   summary="Get email template collection",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/EmailTemplateResource")
     *       ),
     *       @OA\Property(
     *         property="meta",
     *         ref="#/components/schemas/PaginationMeta"
     *       ),
     *       @OA\Property(
     *         property="links",
     *         ref="#/components/schemas/PaginationLinks"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Collection read"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   )
     * )
     *
     * Display a listing of the resource.
     *
     * @return \Motor\Admin\Http\Resources\EmailTemplateCollection
     */
    public function index(): EmailTemplateCollection
    {
        $paginator = EmailTemplateService::collection()
                                         ->getPaginator();

        return (new EmailTemplateCollection($paginator))->additional(['message' => 'Email template collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"EmailTemplatesController"},
     *   path="/api/email_templates",
     *   summary="Create new email template",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/EmailTemplatePostRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/EmailTemplateResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Email template created"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Store a newly created resource in storage.
     *
     * @param \Motor\Admin\Http\Requests\Api\EmailTemplatePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(EmailTemplatePostRequest $request): JsonResponse
    {
        $result = EmailTemplateService::create($request)
                                      ->getResult();

        return (new EmailTemplateResource($result))->additional(['message' => 'Email template created'])
                                                   ->response()
                                                   ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"EmailTemplatesController"},
     *   path="/api/email_templates/{email_template}",
     *   summary="Get single email template",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="email_template",
     *     parameter="email_template",
     *     description="Email template id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/EmailTemplateResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Email template read"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Display the specified resource.
     *
     * @param \Motor\Admin\Models\EmailTemplate $record
     * @return \Motor\Admin\Http\Resources\EmailTemplateResource
     */
    public function show(EmailTemplate $record): EmailTemplateResource
    {
        $result = EmailTemplateService::show($record)
                                      ->getResult();

        return (new EmailTemplateResource($result))->additional(['message' => 'Email template read']);
    }

    /**
     * @OA\Put (
     *   tags={"EmailTemplatesController"},
     *   path="/api/email_templates/{email_template}",
     *   summary="Update an existing email template",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/EmailTemplatePatchRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="email_template",
     *     parameter="email_template",
     *     description="Email template id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/EmailTemplateResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Email template updated"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Update the specified resource in storage.
     *
     * @param \Motor\Admin\Http\Requests\Api\EmailTemplatePatchRequest $request
     * @param \Motor\Admin\Models\EmailTemplate $record
     * @return \Motor\Admin\Http\Resources\EmailTemplateResource
     */
    public function update(EmailTemplatePatchRequest $request, EmailTemplate $record): EmailTemplateResource
    {
        $result = EmailTemplateService::update($record, $request)
                                      ->getResult();

        return (new EmailTemplateResource($result))->additional(['message' => 'Email template updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"EmailTemplatesController"},
     *   path="/api/email_templates/{email_template}",
     *   summary="Delete an email template",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="email_template",
     *     parameter="email_template",
     *     description="Email template id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Email template deleted"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   ),
     *   @OA\Response(
     *     response="400",
     *     description="Bad request",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Problem deleting email template"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param \Motor\Admin\Models\EmailTemplate $record
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(EmailTemplate $record): JsonResponse
    {
        $result = EmailTemplateService::delete($record)
                                      ->getResult();

        if ($result) {
            return response()->json(['message' => 'Email template deleted']);
        }

        return response()->json(['message' => 'Problem deleting email template'], 400);
    }
}
