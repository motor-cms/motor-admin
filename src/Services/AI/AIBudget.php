<?php

namespace Motor\Admin\Services\AI;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Throwable;

/**
 * Cost caps for the AI features, summed from ai_requests.cost_usd.
 *
 * Two levels: per customer and month (budget group, config ai.budget.groups, default the tenant itself) and per user
 * and day across all tenants (ai.intensive raises it). Before each call the worst case of that call is reserved; if
 * spent plus reservation exceeds a limit, the request is refused with 429 BUDGET_EXCEEDED. Days and months follow the
 * app timezone, like the created_at column.
 */
class AIBudget
{
    public const CODE = 'BUDGET_EXCEEDED';

    public static function enabled(): bool
    {
        return (bool) config('ai.budget.enabled', true);
    }

    public static function usdToEur(float $usd): float
    {
        return $usd * (float) config('ai.budget.usd_eur', 0.92);
    }

    public static function groupOf(Client $client): string
    {
        $slug = (string) $client->slug;
        $groups = (array) config('ai.budget.groups', []);

        return (string) ($groups[$slug] ?? ($slug !== '' ? $slug : 'client-'.$client->id));
    }

    /**
     * Ids of all tenants in a budget group (the group key may also be a plain tenant slug).
     *
     * @return list<int>
     */
    public static function groupClientIds(string $group): array
    {
        return Client::withoutGlobalScopes()
            ->get(['id', 'slug'])
            ->filter(fn (Client $c) => self::groupOf($c) === $group)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function groupLimitEur(string $group): float
    {
        $overrides = (array) config('ai.budget.group_month_eur_overrides', []);

        return (float) ($overrides[$group] ?? config('ai.budget.group_month_eur', 60));
    }

    public static function userDayLimitEur(User $user): float
    {
        return AIAccess::userIsIntensive($user)
            ? (float) config('ai.budget.user_day_intensive_eur', 5)
            : (float) config('ai.budget.user_day_eur', 2);
    }

    public static function reserveUsd(string $task): float
    {
        return (float) config('ai.budget.reserve_usd.'.$task, 0.0);
    }

    /**
     * Spent and remaining budget of this user (today) and this tenant's group (this month).
     *
     * @return array{currency: string, remaining_eur: float, images_left: int, user: array<string, mixed>, group: array<string, mixed>}
     */
    public static function status(User $user, Client $client): array
    {
        $now = now();
        $group = self::groupOf($client);

        $userSpentEur = self::usdToEur((float) AIRequest::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->sum('cost_usd'));
        $groupSpentEur = self::usdToEur((float) AIRequest::query()
            ->whereIn('client_id', self::groupClientIds($group))
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->sum('cost_usd'));

        $userLimit = self::userDayLimitEur($user);
        $groupLimit = self::groupLimitEur($group);
        $userRemaining = max(0.0, $userLimit - $userSpentEur);
        $groupRemaining = max(0.0, $groupLimit - $groupSpentEur);
        $remaining = min($userRemaining, $groupRemaining);
        $imageEur = self::usdToEur((float) config('ai.images.price_per_image_usd', 0.10));

        self::warnIfNeeded($group, $groupSpentEur, $groupLimit);

        return [
            'currency'      => 'EUR',
            'remaining_eur' => round($remaining, 2),
            'images_left'   => $imageEur > 0 ? (int) floor($remaining / $imageEur) : 0,
            'user'          => [
                'limit_eur'     => round($userLimit, 2),
                'spent_eur'     => round($userSpentEur, 4),
                'remaining_eur' => round($userRemaining, 2),
                'intensive'     => AIAccess::userIsIntensive($user),
                'resets_at'     => $now->copy()->addDay()->startOfDay()->toIso8601String(),
            ],
            'group' => [
                'name'          => $group,
                'limit_eur'     => round($groupLimit, 2),
                'spent_eur'     => round($groupSpentEur, 4),
                'remaining_eur' => round($groupRemaining, 2),
                'resets_at'     => $now->copy()->addMonthNoOverflow()->startOfMonth()->toIso8601String(),
            ],
        ];
    }

    /**
     * Null if the call may run, otherwise what is exceeded (scope user or group) with message and details.
     *
     * @return array{scope: string, message: string, details: array<string, mixed>}|null
     */
    public static function check(User $user, Client $client, string $task): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $status = self::status($user, $client);
        $reserve = self::usdToEur(self::reserveUsd($task));

        foreach (['user', 'group'] as $scope) {
            $level = $status[$scope];
            if ($level['spent_eur'] + $reserve > $level['limit_eur']) {
                return [
                    'scope'   => $scope,
                    'message' => self::message($scope, $level, $client),
                    'details' => [
                        'scope'         => $scope,
                        'limit_eur'     => $level['limit_eur'],
                        'remaining_eur' => $level['remaining_eur'],
                        'resets_at'     => $level['resets_at'],
                        'budget'        => $status,
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * @param  array{scope: string, message: string, details: array<string, mixed>}  $exceeded
     */
    public static function response(array $exceeded): JsonResponse
    {
        $resetsAt = Carbon::parse($exceeded['details']['resets_at']);

        return response()->json([
            'error' => [
                'code'    => self::CODE,
                'message' => $exceeded['message'],
                'details' => $exceeded['details'] + ['retry_after' => max(0, (int) now()->diffInSeconds($resetsAt))],
            ],
            'meta' => ['api_version' => 'v2'],
        ], 429);
    }

    /** @param array<string, mixed> $level */
    protected static function message(string $scope, array $level, Client $client): string
    {
        $limit = self::formatEur((float) $level['limit_eur']);
        if ($scope === 'user') {
            return 'Ihr KI-Budget für heute ist aufgebraucht (Tageslimit '.$limit.'). Ab morgen, 0 Uhr, geht es weiter.';
        }

        $resets = Carbon::parse($level['resets_at'])->format('d.m.Y');
        $name = $level['name'] === (string) $client->slug ? (string) $client->name : (string) $level['name'];

        return 'Das KI-Budget dieses Monats für '.$name.' ist aufgebraucht ('.$limit.'). Ab dem '.$resets.' geht es weiter. Wenn es eilt, bitte bei zeit:raum melden.';
    }

    public static function formatEur(float $eur): string
    {
        return number_format($eur, 2, ',', '.').' €';
    }

    /**
     * Tell us once per group and month when the group crosses the warning ratio (log, optional mail).
     */
    protected static function warnIfNeeded(string $group, float $spentEur, float $limitEur): void
    {
        $ratio = (float) config('ai.budget.warn_ratio', 0.8);
        if ($limitEur <= 0 || $spentEur < $limitEur * $ratio) {
            return;
        }

        try {
            $key = 'ai-budget-warned:'.$group.':'.now()->format('Y-m');
            if (! Cache::add($key, true, now()->addMonthNoOverflow()->startOfMonth())) {
                return;
            }

            $text = sprintf(
                'KI-Budget %s: %s von %s im %s verbraucht (%d %%).',
                $group,
                self::formatEur($spentEur),
                self::formatEur($limitEur),
                now()->format('m/Y'),
                (int) floor($spentEur / $limitEur * 100)
            );
            Log::warning($text, ['group' => $group, 'spent_eur' => round($spentEur, 4), 'limit_eur' => $limitEur]);

            $mail = config('ai.budget.notify_mail');
            if (is_string($mail) && $mail !== '') {
                Mail::raw($text, fn ($message) => $message->to($mail)->subject('KI-Budget '.$group.' bei '.(int) floor($spentEur / $limitEur * 100).' %'));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
