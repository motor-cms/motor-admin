<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Category;
use Motor\Admin\Models\Client;

pest()->group('Category')->use(RefreshDatabase::class);

describe('Category', function () {
    it('can create a category tree', function () {
        $categorycount = Category::count();
        $this->asAdmin()
            ->post('/api/category_trees', [
                'scope' => 'test',
                'name'  => 'test',
            ])->assertStatus(201);
        expect(Category::count() - $categorycount)->toBe(1);
    });
    it('can get all Category Trees')
        ->asAdmin()
        ->get('/api/category_trees')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            6,
            fn (AssertableJson $data) => $data
                ->has('id')
                ->has('name')
                ->has('scope')
                ->etc()
        )->etc());
    it(
        'can get a specific Category tree',
        fn () => $this->asAdmin()->get('/api/category_trees/'.Category::whereName('Default')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('scope')
                    ->has('children', 3, fn (AssertableJson $json) => $json
                        ->has('name')
                        ->has('id')
                        ->etc())

            )->etc())
    );

    it('can create a subcategory', function () {
        $categorycount = Category::count();
        $this->asAdmin()
            ->post('/api/category_trees/'.Category::whereName('Default')->first()->id.'/categories', [
                'parent_id' => Category::whereName('Default')->first()->id,
                'name'      => 'test',
            ])->assertStatus(201);
        expect(Category::count() - $categorycount)->toBe(1);
    });
    it("can't create a subcategory with a wrong parent id", function () {
        $categoryTreeId = Category::whereName('Media')->first();
        $parentId = Category::whereName('Default')->first();
        $this->asAdmin()
            ->withJsonHeaders()
            ->post('/api/category_trees/'.$categoryTreeId.'/categories', [
                'parent_id' => $parentId,
                'name'      => 'test',
            ])->assertStatus(422);
    });
    it("can't create a Category with invalid parent", function () {
        $categorycount = Category::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/category_trees', [
                'parent_id' => 0,
                'name'      => 'test',
            ])->assertStatus(422);
        expect(Category::count() - $categorycount)->toBe(0);
    });
    it("can't create a Category with invalid sibling", function () {
        $categorycount = Category::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/category_trees', [
                'previous_sibling_id' => 0,
                'parent_id'           => Category::whereName('Default')->first()->id,
                'name'                => 'test',
            ])->assertStatus(422);
        expect(Category::count() - $categorycount)->toBe(0);
    });
    it("can't create an empty Category", function () {
        $categorycount = Category::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/category_trees', [])->assertStatus(422);
        expect(Category::count() - $categorycount)->toBe(0);
    });
    it(
        'can get all Categorys',
        fn () => $this
            ->asAdmin()
            ->get('/api/category_trees/'.Category::whereName('Default')->first()->id.'/categories')
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                3,
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('scope')
                    ->etc()
            )->etc())
    );
    it('can update categories', fn () => $this->asAdmin()
        ->put('/api/category_trees/'.Category::whereName('Test #1')->first()->id, [
            'client_id' => Client::first()->id,
            'name'      => 'changed',
            'scope'     => 'test',
        ])->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data->where('name', 'changed')->etc())->etc()));
});
