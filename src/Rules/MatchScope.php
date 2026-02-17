<?php

namespace Motor\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Motor\Admin\Models\Category;

class MatchScope implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = Category::find($value);
        $categoryTree = Category::find(request()->route()->parameter('category_tree'));

        // Weird workaround for the case when the category tree is not found
        // This can happen if the route parameter is not set or the category tree does not exist
        if ($categoryTree instanceof Collection) {
            $categoryTree = $categoryTree->first();
        }

        if (! is_null($parent) && ! is_null($categoryTree)) {
            if ($parent->scope !== $categoryTree->scope) {
                $fail('Scopes do not match');
            }
        }
    }
}
