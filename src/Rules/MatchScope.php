<?php

namespace Motor\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Motor\Admin\Models\Category;

class MatchScope implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = Category::find($value);
        $categoryTree = Category::find(request()->route()->parameter('category_tree'))->first();
        if (!is_null($parent) && !is_null($categoryTree)) {
            if ($parent->scope !== $categoryTree->scope) {
                $fail('Scopes do not match');
            }
        }
    }
}
