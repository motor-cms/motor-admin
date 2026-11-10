<?php

namespace Motor\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Admin\Services\AI\AIBudget;

/**
 * Monthly AI cost per tenant, budget group and user from ai_requests, as the basis for billing and for checking the
 * Mistral invoice. Euro at the fixed rate ai.budget.usd_eur.
 */
class MotorAIUsageCommand extends Command
{
    protected $signature = 'motor:ai:usage
        {--month= : Month as YYYY-MM, default the current month}
        {--json : Print the totals as JSON instead of tables}';

    protected $description = 'Show AI costs of one month per tenant, budget group and user';

    public function handle(): int
    {
        $month = (string) ($this->option('month') ?: now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error('--month must look like 2026-11.');

            return self::FAILURE;
        }

        $from = Carbon::createFromFormat('Y-m-d H:i:s', $month.'-01 00:00:00');
        $to = $from->copy()->addMonthNoOverflow();
        $base = fn () => AIRequest::query()->where('created_at', '>=', $from)->where('created_at', '<', $to);
        $columns = [DB::raw('COUNT(*) as requests'), DB::raw('SUM(cost_usd) as cost_usd')];

        $clients = Client::withoutGlobalScopes()->get(['id', 'name', 'slug'])->keyBy('id');
        $perClient = $base()->select(array_merge(['client_id'], $columns))->groupBy('client_id')->get()
            ->map(function ($row) use ($clients) {
                $client = $clients->get($row->client_id);

                return [
                    'client_id' => $row->client_id !== null ? (int) $row->client_id : null,
                    'tenant'    => $client ? ($client->slug ?: $client->name) : '(ohne Mandant)',
                    'group'     => $client ? AIBudget::groupOf($client) : '-',
                    'requests'  => (int) $row->requests,
                    'cost_usd'  => round((float) $row->cost_usd, 4),
                    'cost_eur'  => round(AIBudget::usdToEur((float) $row->cost_usd), 2),
                ];
            })
            ->sortByDesc('cost_usd')->values();

        $perGroup = $perClient->groupBy('group')->map(fn ($rows, $group) => [
            'group'     => $group,
            'requests'  => $rows->sum('requests'),
            'cost_usd'  => round($rows->sum('cost_usd'), 4),
            'cost_eur'  => round(AIBudget::usdToEur($rows->sum('cost_usd')), 2),
            'limit_eur' => $group === '-' ? null : AIBudget::groupLimitEur((string) $group),
        ])->sortByDesc('cost_usd')->values();

        $users = User::query()->whereIn('id', $base()->whereNotNull('user_id')->distinct()->pluck('user_id'))->get(['id', 'name', 'email'])->keyBy('id');
        $perUser = $base()->select(array_merge(['user_id'], $columns))->groupBy('user_id')->get()
            ->map(function ($row) use ($users) {
                $user = $users->get($row->user_id);

                return [
                    'user_id'  => $row->user_id !== null ? (int) $row->user_id : null,
                    'user'     => $user ? ($user->email ?: $user->name) : '(unbekannt)',
                    'requests' => (int) $row->requests,
                    'cost_usd' => round((float) $row->cost_usd, 4),
                    'cost_eur' => round(AIBudget::usdToEur((float) $row->cost_usd), 2),
                ];
            })
            ->sortByDesc('cost_usd')->values();

        $total = [
            'requests' => $perClient->sum('requests'),
            'cost_usd' => round($perClient->sum('cost_usd'), 4),
            'cost_eur' => round(AIBudget::usdToEur($perClient->sum('cost_usd')), 2),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'month'   => $month,
                'usd_eur' => (float) config('ai.budget.usd_eur', 0.92),
                'tenants' => $perClient,
                'groups'  => $perGroup,
                'users'   => $perUser,
                'total'   => $total,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $eur = fn (float $v) => number_format($v, 2, ',', '.');
        $usd = fn (float $v) => number_format($v, 4, ',', '.');

        $this->info('KI-Kosten '.$month.' (Kurs '.config('ai.budget.usd_eur', 0.92).' EUR/USD)');
        $this->table(['Mandant', 'Gruppe', 'Anfragen', 'USD', 'EUR'], $perClient->map(fn ($r) => [
            $r['tenant'], $r['group'], $r['requests'], $usd($r['cost_usd']), $eur($r['cost_eur']),
        ])->all());
        $this->table(['Gruppe', 'Anfragen', 'USD', 'EUR', 'Limit EUR'], $perGroup->map(fn ($r) => [
            $r['group'], $r['requests'], $usd($r['cost_usd']), $eur($r['cost_eur']), $r['limit_eur'] !== null ? $eur($r['limit_eur']) : '-',
        ])->all());
        $this->table(['Nutzer', 'Anfragen', 'USD', 'EUR'], $perUser->map(fn ($r) => [
            $r['user'], $r['requests'], $usd($r['cost_usd']), $eur($r['cost_eur']),
        ])->all());
        $this->line(sprintf('Summe: %d Anfragen, %s USD, %s EUR', $total['requests'], $usd($total['cost_usd']), $eur($total['cost_eur'])));

        return self::SUCCESS;
    }
}
