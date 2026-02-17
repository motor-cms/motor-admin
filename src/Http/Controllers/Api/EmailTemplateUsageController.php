<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\ContentType\Models\FormConfig;

class EmailTemplateUsageController extends ApiController
{
    /**
     * Get all forms and pages where the email template is used
     */
    public function usage(string $template_id): JsonResponse
    {
        // Find all form configs that use this email template
        // Wrap the OR conditions in a closure to ensure proper query building
        $formConfigs = FormConfig::where(function ($query) use ($template_id) {
            $query->where('user_email_template_id', $template_id)
                ->orWhere('target_email_template_id', $template_id);
        })
            ->whereHas('builderPage', fn ($query) => $query->where('is_current', true)) // Only current versions of pages
            ->with(['builderPage', 'customContentType'])
            ->get();

        // Transform the data to return useful information
        $usage = $formConfigs->map(function ($formConfig) use ($template_id) {
            $builderPage = $formConfig->builderPage;
            $customContentType = $formConfig->customContentType;

            $usageType = [];
            if ($formConfig->user_email_template_id == $template_id) {
                $usageType[] = 'user_email';
            }
            if ($formConfig->target_email_template_id == $template_id) {
                $usageType[] = 'target_email';
            }

            return [
                'form_config_id' => $formConfig->id,
                'form_component_uuid' => $formConfig->form_component_uuid,
                'form_name' => $customContentType->name ?? 'Unbekanntes Formular', // Get name from CustomContentType
                'custom_content_type_id' => $customContentType->id ?? null, // Add custom_content_type_id
                'usage_type' => $usageType,
                'builder_page' => $builderPage ? [
                    'id' => $builderPage->id,
                    'name' => $builderPage->name,
                    'is_published' => $builderPage->is_published,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $usage,
            'message' => 'Email template usage retrieved successfully',
        ]);
    }
}
