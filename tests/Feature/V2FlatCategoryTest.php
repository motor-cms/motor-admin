<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Category;

pest()->group('V2FlatCategory')->use(RefreshDatabase::class);

describe('V2 Flat Categories API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/categories');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('returns all categories including roots and children', function () {
        $totalCategories = Category::count();

        $response = $this->asAdmin()->getJson('/api/v2/categories?per_page=0');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe($totalCategories);
    });

    it('can filter categories by scope', function () {
        $response = $this->asAdmin()->getJson('/api/v2/categories?scope=default&per_page=0');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);

        foreach ($data as $item) {
            expect($item['scope'])->toBe('default');
        }
    });

    it('returns empty data for nonexistent scope', function () {
        $response = $this->asAdmin()->getJson('/api/v2/categories?scope=nonexistent');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('denies access to basic users', function () {
        $this->asBasic()->getJson('/api/v2/categories')->assertStatus(403);
    });
});
