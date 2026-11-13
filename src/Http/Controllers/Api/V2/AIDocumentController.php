<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\V2\AIBuilderDocumentPostRequest;
use Motor\Admin\Services\AI\AIAccess;
use Motor\Admin\Services\AI\AIBudget;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\AIRequestLogger;
use Motor\Admin\Services\AI\Documents\DocumentExtractor;
use Motor\Admin\Services\AI\Documents\DocumentUnreadableException;

/**
 * KI im Page Builder: a document as source ("Dokument anhängen"). Returns the document as Markdown; the dialog
 * sends it along with the next section/revise prompts. Nothing is stored, ai_requests gets task builder.document
 * with the page cost (no file name, no content).
 */
class AIDocumentController extends ApiController
{
    public function extract(AIBuilderDocumentPostRequest $request, DocumentExtractor $extractor): JsonResponse
    {
        $user = $request->user();
        $client = AIAccess::authorize($user, AIAccess::findClient($request->integer('client_id')));
        if (! config('ai.documents.enabled', true)) {
            return $this->error('FORBIDDEN', 'Dokumente sind für die KI nicht freigeschaltet.', 403);
        }
        if ($exceeded = AIBudget::check($user, $client, 'document')) {
            return AIBudget::response($exceeded);
        }

        $model = (string) config('ai.documents.model');
        $started = hrtime(true);
        $log = fn (string $status, int $pages = 0, ?string $error = null, ?string $usedModel = null) => AIRequestLogger::log(
            task: 'builder.document',
            clientId: $client->id,
            userId: $user->id,
            provider: 'mistral',
            model: $usedModel ?: $model,
            status: $status,
            durationMs: (int) ((hrtime(true) - $started) / 1_000_000),
            error: $error,
            extraCostUsd: DocumentExtractor::costUsd($pages),
        );

        try {
            $document = $extractor->extract($request->file('file'));
        } catch (DocumentUnreadableException $e) {
            $log('invalid', 0, $e->getMessage());

            return $this->error('AI_DOCUMENT_UNREADABLE', 'In dem Dokument wurde kein Text gefunden. Ist es ein Scan ohne Text oder leer?', 422);
        } catch (AIProviderException $e) {
            $log('error', 0, $e->getMessage());

            return $this->error('AI_PROVIDER_ERROR', 'Das Dokument konnte gerade nicht gelesen werden. Bitte später erneut versuchen.', 503);
        }

        $log('ok', $document['pages'], null, $document['model']);

        return response()->json([
            'data' => [
                'name'      => $document['name'],
                'pages'     => $document['pages'],
                'max_pages' => (int) config('ai.documents.max_pages', 30),
                'chars'     => $document['chars'],
                'truncated' => $document['truncated'],
                'markdown'  => $document['markdown'],
            ],
            'meta' => [
                'api_version' => 'v2',
                'cost_usd'    => DocumentExtractor::costUsd($document['pages']),
                'budget'      => AIBuilderController::budgetMeta($user, $client),
            ],
        ]);
    }

    protected function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message],
            'meta'  => ['api_version' => 'v2'],
        ], $status);
    }
}
