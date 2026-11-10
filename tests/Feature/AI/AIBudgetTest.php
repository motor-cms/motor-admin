<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;
use Spatie\Permission\PermissionRegistrar;

pest()->group('AIBuilder')->use(RefreshDatabase::class);

function budgetClient(string $slug, string $name = 'Mandant'): Client
{
    $client = Client::factory()->create(['slug' => $slug, 'name' => $name]);
    $client->ai_config = ['enabled' => true];
    $client->save();

    return $client;
}

function budgetUser(Client $client, array $permissions = ['ai.use']): User
{
    $user = User::factory()->create();
    $user->clients()->attach($client->id);
    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

/** One logged AI call with a given cost; created_at defaults to now (travelTo in beforeEach). */
function spend(?User $user, Client $client, float $usd, ?Carbon $at = null, string $task = 'builder.section'): AIRequest
{
    $row = AIRequest::create([
        'client_id' => $client->id,
        'user_id'   => $user?->id,
        'task'      => $task,
        'provider'  => 'mistral',
        'model'     => 'mistral-medium-2604',
        'status'    => 'ok',
        'cost_usd'  => $usd,
    ]);
    if ($at !== null) {
        $row->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }

    return $row;
}

function budgetSectionBody(Client $client): array
{
    return [
        'client_id'        => $client->id,
        'prompt'           => 'Hero zu Fernwärme',
        'catalog_manifest' => json_decode(file_get_contents(__DIR__.'/../../Fixtures/ai/manifest-mini.json'), true),
        'max_sections'     => 1,
    ];
}

function budgetMistralOk(): void
{
    Http::fake(['api.mistral.ai/*' => Http::response([
        'id'      => 'cmpl-test',
        'model'   => 'mistral-medium-2604',
        'choices' => [[
            'index'         => 0,
            'finish_reason' => 'stop',
            'message'       => ['role' => 'assistant', 'content' => json_encode([
                'sections' => [[
                    'block'  => 'ui-page-hero',
                    'fields' => ['title' => 'Wärme für Ihr Zuhause', 'description' => 'Fernwärme einfach erklärt.', 'orientation' => 'horizontal'],
                    'lists'  => (object) [],
                    'media'  => [],
                ]],
                'placeholders' => [],
            ])],
        ]],
        'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 200, 'total_tokens' => 1200],
    ])]);
}

beforeEach(function () {
    config([
        'ai.mistral.api_key'               => 'test-key',
        'ai.mistral.models.builder'        => 'mistral-medium-2604',
        'ai.images.price_per_image_usd'    => 0.1,
        'ai.budget.enabled'                => true,
        'ai.budget.usd_eur'                => 0.92,
        'ai.budget.group_month_eur'        => 60,
        'ai.budget.user_day_eur'           => 2,
        'ai.budget.user_day_intensive_eur' => 5,
        'ai.budget.notify_mail'            => null,
    ]);
    Sleep::fake();
    $this->travelTo(Carbon::parse('2026-11-15 12:00:00'));
});

describe('permissions ai.use and ai.intensive', function () {
    it('answers 403 on all AI endpoints without ai.use and hides AI in the status', function () {
        Http::fake();
        $client = budgetClient('budget-a');
        $user = budgetUser($client, []);

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($client))->assertForbidden();
        $this->actingAs($user)->postJson('/api/v2/ai/builder/revise', budgetSectionBody($client) + ['scope' => 'page', 'sections' => [['ref' => 's1', 'block' => 'ui-page-hero', 'fields' => ['title' => 'Alt']]]])->assertForbidden();
        $this->actingAs($user)->postJson('/api/v2/ai/builder/image', ['client_id' => $client->id, 'prompt' => 'Wärmepumpe vor einem Haus'])->assertForbidden();
        $prompt = AISystemPrompt::create(['client_id' => $client->id, 'name' => 'Ton', 'prompt' => 'Sie-Form.']);
        $this->actingAs($user)->postJson('/api/ai_help', ['prompt' => 'Hallo', 'system_prompt' => $prompt->id])->assertForbidden();

        $this->actingAs($user)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertOk()
            ->assertJsonPath('data.allowed', false)
            ->assertJsonPath('data.budget', null);
        Http::assertNothingSent();
    });

    it('shows the daily budget in the status, higher with ai.intensive', function () {
        $client = budgetClient('budget-a');
        $normal = budgetUser($client);
        $intensive = budgetUser($client, ['ai.use', 'ai.intensive']);
        spend($normal, $client, 0.5); // 0,46 €

        $this->actingAs($normal)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertJsonPath('data.allowed', true)
            ->assertJsonPath('data.budget.user.limit_eur', 2)
            ->assertJsonPath('data.budget.user.intensive', false)
            ->assertJsonPath('data.budget.remaining_eur', 1.54)
            ->assertJsonPath('data.budget.images_left', 16)
            ->assertJsonPath('data.budget.user.resets_at', Carbon::parse('2026-11-16 00:00:00')->toIso8601String());

        $this->actingAs($intensive)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertJsonPath('data.budget.user.limit_eur', 5)
            ->assertJsonPath('data.budget.user.intensive', true);
    });

    it('moves builder-pages.ai grants to ai.use, ai.intensive only for SuperAdmin and Editor', function () {
        $old = Permission::create(['name' => 'builder-pages.ai', 'guard_name' => 'web']);
        $roles = collect(['SuperAdmin', 'Editor', 'Writer', 'KI-Redaktion'])
            ->mapWithKeys(fn (string $name) => [$name => Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])]);
        $roles->each->givePermissionTo($old);
        $direct = User::factory()->create();
        $direct->givePermissionTo($old);

        $migration = require __DIR__.'/../../../database/migrations/2026_11_10_120000_add_ai_use_and_ai_intensive_permissions.php';
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($roles as $name => $role) {
            expect($role->fresh()->hasPermissionTo('ai.use'))->toBeTrue("{$name} keeps AI")
                ->and($role->fresh()->hasPermissionTo('ai.intensive'))
                ->toBe(in_array($name, ['SuperAdmin', 'Editor'], true), "{$name} ai.intensive");
        }
        expect($direct->fresh()->hasPermissionTo('ai.use'))->toBeTrue()
            ->and($direct->fresh()->hasDirectPermission('ai.intensive'))->toBeFalse()
            ->and(Permission::where('name', 'builder-pages.ai')->exists())->toBeFalse();
    });

    it('gives ai.intensive to SuperAdmin and Editor only if they had builder-pages.ai', function () {
        Permission::create(['name' => 'builder-pages.ai', 'guard_name' => 'web']);
        $editor = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
        $editor->syncPermissions([]);

        $migration = require __DIR__.'/../../../database/migrations/2026_11_10_120000_add_ai_use_and_ai_intensive_permissions.php';
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        expect($editor->fresh()->hasPermissionTo('ai.use'))->toBeFalse()
            ->and($editor->fresh()->hasPermissionTo('ai.intensive'))->toBeFalse();
    });
});

describe('budget check (429 BUDGET_EXCEEDED)', function () {
    it('stops a user at the daily cap before calling Mistral', function () {
        Http::fake();
        $client = budgetClient('budget-a');
        $user = budgetUser($client);
        spend($user, $client, 2.1); // 1,93 € + Reserve 0,07 € > 2 €

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($client))
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'BUDGET_EXCEEDED')
            ->assertJsonPath('error.details.scope', 'user')
            ->assertJsonPath('error.details.limit_eur', 2)
            ->assertJsonPath('error.details.remaining_eur', 0.07)
            ->assertJsonPath('error.details.resets_at', Carbon::parse('2026-11-16 00:00:00')->toIso8601String())
            ->assertJsonPath('error.details.retry_after', 12 * 3600)
            ->assertJsonPath('error.message', 'Ihr KI-Budget für heute ist aufgebraucht (Tageslimit 2,00 €). Ab morgen, 0 Uhr, geht es weiter.');
        Http::assertNothingSent();
    });

    it('counts the user across all tenants and only today', function () {
        budgetMistralOk();
        $a = budgetClient('budget-a');
        $b = budgetClient('budget-b');
        $user = budgetUser($a);
        $user->clients()->attach($b->id);
        spend($user, $b, 3.0, Carbon::parse('2026-11-14 18:00:00')); // gestern: zählt nicht
        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($a))
            ->assertOk()
            ->assertJsonPath('meta.budget.user.limit_eur', 2);

        spend($user, $b, 2.1); // heute, anderer Mandant
        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($a))
            ->assertStatus(429)
            ->assertJsonPath('error.details.scope', 'user');
    });

    it('lets ai.intensive users go up to 5 €', function () {
        budgetMistralOk();
        $client = budgetClient('budget-a');
        $user = budgetUser($client, ['ai.use', 'ai.intensive']);
        spend($user, $client, 2.1);

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($client))->assertOk();

        spend($user, $client, 3.3); // zusammen 4,96 €
        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($client))
            ->assertStatus(429)
            ->assertJsonPath('error.details.limit_eur', 5);
    });

    it('reserves more for an image than for a section', function () {
        budgetMistralOk();
        $client = budgetClient('budget-a');
        $user = budgetUser($client);
        spend($user, $client, 2.08); // 1,91 €: Abschnitt (0,07 €) passt, Bild (0,10 €) nicht

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($client))->assertOk();
        AIRequest::query()->where('task', 'builder.section')->where('cost_usd', '<', 1)->delete();

        $this->actingAs($user)->postJson('/api/v2/ai/builder/image', ['client_id' => $client->id, 'prompt' => 'Wärmepumpe vor einem Haus'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'BUDGET_EXCEEDED')
            ->assertJsonPath('error.details.scope', 'user');
    });

    it('stops the whole customer group at the monthly cap, other customers keep going', function () {
        budgetMistralOk();
        config(['ai.budget.groups' => ['energisde' => 'energis', 'glasfaserenergisde' => 'energis']]);
        $energis = budgetClient('energisde', 'energis.de');
        $glasfaser = budgetClient('glasfaserenergisde', 'glasfaser.energis.de');
        $other = budgetClient('anderer-kunde', 'Anderer Kunde');
        spend(null, $glasfaser, 65.2, Carbon::parse('2026-11-02 09:00:00')); // 59,98 € in Glasfaser
        spend(null, $energis, 50.0, Carbon::parse('2026-10-30 09:00:00')); // Vormonat: zählt nicht
        $user = budgetUser($energis);
        $user->clients()->attach($other->id);

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($energis))
            ->assertStatus(429)
            ->assertJsonPath('error.details.scope', 'group')
            ->assertJsonPath('error.details.budget.group.name', 'energis')
            ->assertJsonPath('error.details.remaining_eur', 0.02)
            ->assertJsonPath('error.details.resets_at', Carbon::parse('2026-12-01 00:00:00')->toIso8601String())
            ->assertJsonPath('error.message', 'Das KI-Budget dieses Monats für energis ist aufgebraucht (60,00 €). Ab dem 01.12.2026 geht es weiter. Wenn es eilt, bitte bei zeit:raum melden.');

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($other))->assertOk();
    });

    it('also blocks the text help when the budget is used up', function () {
        config(['ai.provider' => 'mistral']);
        Http::fake();
        $client = budgetClient('budget-a');
        $user = budgetUser($client);
        $prompt = AISystemPrompt::create(['client_id' => $client->id, 'name' => 'Ton', 'prompt' => 'Sie-Form.']);
        spend($user, $client, 2.2);

        $this->actingAs($user)->postJson('/api/ai_help', ['prompt' => 'Hallo', 'system_prompt' => $prompt->id])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'BUDGET_EXCEEDED');
        Http::assertNothingSent();
    });

    it('does nothing when switched off', function () {
        budgetMistralOk();
        config(['ai.budget.enabled' => false]);
        $client = budgetClient('budget-a');
        $user = budgetUser($client);
        spend($user, $client, 10.0);

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', budgetSectionBody($client))
            ->assertOk()
            ->assertJsonPath('meta.budget', null);
    });

    it('warns us once per group and month at 80 %', function () {
        Log::spy();
        $client = budgetClient('budget-a');
        $user = budgetUser($client);
        spend(null, $client, 53.0); // 48,76 € = 81 %

        $this->actingAs($user)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)->assertOk();
        $this->actingAs($user)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)->assertOk();

        Log::shouldHaveReceived('warning')->withArgs(fn (string $msg) => str_contains($msg, 'KI-Budget budget-a') && str_contains($msg, '81 %'))->once();
    });
});

describe('motor:ai:usage', function () {
    it('sums one month per tenant, group and user', function () {
        config(['ai.budget.groups' => ['energisde' => 'energis', 'glasfaserenergisde' => 'energis']]);
        $energis = budgetClient('energisde');
        $glasfaser = budgetClient('glasfaserenergisde');
        $other = budgetClient('anderer-kunde');
        $anna = budgetUser($energis);
        $ben = budgetUser($other);
        spend($anna, $energis, 1.0, Carbon::parse('2026-11-03 10:00:00'));
        spend($anna, $glasfaser, 0.5, Carbon::parse('2026-11-20 10:00:00'));
        spend($ben, $other, 0.25, Carbon::parse('2026-11-30 23:59:00'));
        spend($ben, $other, 9.0, Carbon::parse('2026-12-01 00:00:00')); // anderer Monat

        expect(Artisan::call('motor:ai:usage', ['--month' => '2026-11', '--json' => true]))->toBe(0);
        $out = json_decode(Artisan::output(), true);

        expect($out['month'])->toBe('2026-11')
            ->and($out['total'])->toBe(['requests' => 3, 'cost_usd' => 1.75, 'cost_eur' => 1.61])
            ->and(collect($out['tenants'])->firstWhere('tenant', 'energisde'))->toMatchArray(['group' => 'energis', 'requests' => 1, 'cost_usd' => 1.0])
            ->and(collect($out['groups'])->firstWhere('group', 'energis'))->toMatchArray(['requests' => 2, 'cost_usd' => 1.5, 'cost_eur' => 1.38, 'limit_eur' => 60.0])
            ->and(collect($out['users'])->firstWhere('user_id', $ben->id))->toMatchArray(['requests' => 1, 'cost_usd' => 0.25]);
    });

    it('prints tables and rejects a malformed month', function () {
        $client = budgetClient('budget-a');
        spend(budgetUser($client), $client, 0.4);

        $this->artisan('motor:ai:usage')
            ->expectsOutputToContain('KI-Kosten 2026-11')
            ->expectsOutputToContain('Summe: 1 Anfragen, 0,4000 USD, 0,37 EUR')
            ->assertSuccessful();
        $this->artisan('motor:ai:usage', ['--month' => '11/2026'])->assertFailed();
    });
});
