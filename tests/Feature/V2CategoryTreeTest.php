<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Category;
use Motor\Admin\Models\Client;

pest()->group('V2CategoryTree')->use(RefreshDatabase::class);

describe('V2 CategoryTree API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/category-trees');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all category trees', function () {
        assertV2CrudIndex('/api/v2/category-trees', 6, ['id', 'name', 'scope']);
    });

    it('can get a specific category tree', function () {
        $response = $this->asAdmin()
            ->getJson('/api/v2/category-trees/'.Category::whereName('Default')->first()->id);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('scope')
                    ->has('children', 3)
                    ->etc()
            )->etc());
    });

    it('can create a category tree', function () {
        assertV2CrudCreate('/api/v2/category-trees', [
            'scope' => 'v2-test',
            'name' => 'V2 Test Category Tree',
        ], Category::class);
    });

    it('validates required fields on create', function () {
        $countBefore = Category::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/category-trees', [])
            ->assertStatus(422);
        expect(Category::count() - $countBefore)->toBe(0);
    });

    it("can't create a category tree with invalid parent", function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/category-trees', [
                'parent_id' => 0,
                'name' => 'Invalid Parent',
            ])
            ->assertStatus(422);
    });

    it('can update a category tree', function () {
        assertV2CrudUpdate(
            '/api/v2/category-trees/'.Category::whereName('Test #1')->first()->id,
            [
                'client_id' => Client::first()->id,
                'name' => 'V2 Updated Category',
                'scope' => 'v2-updated',
            ],
            'name',
            'V2 Updated Category'
        );
    });

    // Subcategories
    it('can create a subcategory', function () {
        $categoryTree = Category::whereName('Default')->first();
        $countBefore = Category::count();

        $response = $this->asAdmin()
            ->post('/api/v2/category-trees/'.$categoryTree->id.'/categories', [
                'parent_id' => $categoryTree->id,
                'name' => 'V2 Subcategory',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('meta.api_version', 'v2');
        expect(Category::count() - $countBefore)->toBe(1);
    });

    it('can get all categories of a tree', function () {
        $categoryTree = Category::whereName('Default')->first();

        $response = $this->asAdmin()
            ->getJson('/api/v2/category-trees/'.$categoryTree->id.'/categories');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonCount(3, 'data');
    });

    it("can't create a subcategory with wrong parent", function () {
        $categoryTreeId = Category::whereName('Media')->first()->id;
        $wrongParentId = Category::whereName('Default')->first()->id;

        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/category-trees/'.$categoryTreeId.'/categories', [
                'parent_id' => $wrongParentId,
                'name' => 'Invalid Subcategory',
            ])
            ->assertStatus(422);
    });

    it('can delete a category tree with 204 No Content', function () {
        $categoryTree = Category::whereName('Test #1')->first();

        assertV2CrudDelete('/api/v2/category-trees/'.$categoryTree->id, Category::class);
    });

    it('can get a category tree by scope', function () {
        $response = $this->asAdmin()
            ->getJson('/api/v2/category-trees/scope/default');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->where('scope', 'default')
                    ->has('children')
                    ->etc()
            )->etc());
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/category-trees', Category::whereName('Default')->first()->id);
    });

    it('can filter category trees by scope', function () {
        $response = $this->asAdmin()
            ->getJson('/api/v2/category-trees?scope=default');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);

        // All returned trees should have scope 'default'
        foreach ($data as $item) {
            expect($item['scope'])->toBe('default');
        }
    });
});
