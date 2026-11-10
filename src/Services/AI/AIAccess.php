<?php

namespace Motor\Admin\Services\AI;

use Illuminate\Auth\Access\AuthorizationException;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;

/**
 * Who may use AI features for which tenant.
 *
 * Three gates, all must pass: the tenant switch (clients.ai_config.enabled,
 * default off), access to that tenant (users_client pivot, SuperAdmin sees all)
 * and the permission ai.use (SuperAdmin implicitly). ai.intensive only raises the daily budget (AIBudget).
 */
class AIAccess
{
    public const PERMISSION = 'ai.use';

    public const INTENSIVE_PERMISSION = 'ai.intensive';

    public static function clientEnabled(?Client $client): bool
    {
        return $client !== null && data_get($client->ai_config, 'enabled') === true;
    }

    public static function userHasPermission(User $user): bool
    {
        return $user->isAdmin() || $user->checkPermissionTo(self::PERMISSION);
    }

    public static function userIsIntensive(User $user): bool
    {
        return $user->isAdmin() || $user->checkPermissionTo(self::INTENSIVE_PERMISSION);
    }

    public static function userCanAccessClient(User $user, Client $client): bool
    {
        return $user->isAdmin() || $user->clients()->where('clients.id', $client->id)->exists();
    }

    /**
     * Resolve the tenant without the request-bound ClientScope (also used on V1 routes).
     */
    public static function findClient(mixed $clientId): ?Client
    {
        if (! is_numeric($clientId)) {
            return null;
        }

        return Client::withoutGlobalScopes()->find((int) $clientId);
    }

    /**
     * @throws AuthorizationException
     */
    public static function authorize(User $user, ?Client $client): Client
    {
        if ($client === null || ! self::userCanAccessClient($user, $client)) {
            throw new AuthorizationException('Kein Zugriff auf diesen Mandanten.');
        }
        if (! self::clientEnabled($client)) {
            throw new AuthorizationException('KI ist für diesen Mandanten nicht freigeschaltet.');
        }
        if (! self::userHasPermission($user)) {
            throw new AuthorizationException('Ihnen fehlt das Recht '.self::PERMISSION.'.');
        }

        return $client;
    }

    public static function tonePrompt(Client $client): ?string
    {
        $id = data_get($client->ai_config, 'tone_prompt_id');
        if (! $id) {
            return null;
        }

        $prompt = AISystemPrompt::find($id);

        return $prompt && (int) $prompt->client_id === (int) $client->id ? $prompt->prompt : null;
    }
}
