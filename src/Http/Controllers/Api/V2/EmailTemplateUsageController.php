<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Motor\Admin\Http\Resources\V2\EmailTemplateUsageResource;
use Motor\ContentType\Models\FormConfig;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Email Templates
 */
class EmailTemplateUsageController extends ApiController
{
    /**
     * Get all forms and pages where the email template is used
     *
     * @response AnonymousResourceCollection<EmailTemplateUsageResource>
     */
    public function usage(string $emailTemplate): AnonymousResourceCollection
    {
        $formConfigs = FormConfig::where(function ($query) use ($emailTemplate) {
            $query->where('user_email_template_id', $emailTemplate)
                ->orWhere('target_email_template_id', $emailTemplate);
        })
            ->whereHas('builderPage', fn ($query) => $query->where('is_current', true))
            ->with(['builderPage', 'customContentType'])
            ->get();

        $usage = $formConfigs->map(function ($formConfig) use ($emailTemplate) {
            $builderPage = $formConfig->builderPage;
            $customContentType = $formConfig->customContentType;

            $usageType = [];
            if ($formConfig->user_email_template_id == $emailTemplate) {
                $usageType[] = 'user_email';
            }
            if ($formConfig->target_email_template_id == $emailTemplate) {
                $usageType[] = 'target_email';
            }

            return [
                'form_config_id' => $formConfig->id,
                'form_component_uuid' => $formConfig->form_component_uuid,
                'form_name' => $customContentType->name ?? 'Unbekanntes Formular',
                'custom_content_type_id' => $customContentType->id ?? null,
                'usage_type' => $usageType,
                'builder_page' => $builderPage ? [
                    'id' => $builderPage->id,
                    'name' => $builderPage->name,
                    'is_published' => $builderPage->is_published,
                ] : null,
            ];
        });

        return EmailTemplateUsageResource::collection($usage)
            ->additional(['meta' => ['message' => 'Email template usage retrieved']]);
    }
}
