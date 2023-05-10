<?php

namespace Motor\Admin\Services;

use Motor\Admin\Models\Category;
use Motor\Core\Filter\Renderers\WhereRenderer;

/**
 * Class CategoryService
 */
class CategoryService extends BaseService
{
    protected $model = Category::class;

    public function filters(): void
    {
        $this->filter->add(new WhereRenderer('parent_id'));

        $searchFilter = $this->getFilter()
            ->get('search');
        $model = $this->model;
        if (! is_object($this->model)) {
            $model = new $this->model();
        }
        $searchFilter->setSearchableColumns($model->getSearchableColumns());
    }

    public function beforeCreate(): void
    {
        $this->setTreePosition();
    }

    public function beforeUpdate(): void
    {
        $this->setTreePosition();
    }

    protected function setTreePosition(): void
    {
        // Get previous sibling (if it exists)
        $node = Category::find($this->request->get('previous_sibling_id'));

        // If it exists, append the item AFTER the node, but only if it has been changed
        if (! is_null($node)) {
            $this->record->scope = $node->scope;
            $formerPreviousSibling = null;
            if ($this->record->exists) {
                $formerPreviousSibling = $this->record->getPrevSibling();
            }
            if ((is_null($formerPreviousSibling) || ($formerPreviousSibling->id != $node->id))) {
                $this->record->afterNode($node);
            }
        }

        // Get next sibling, if the previous sibling didn't exist
        if (is_null($node)) {
            $node = Category::find($this->request->get('next_sibling_id'));

            // If it exists, append the item BEFORE the node, but only if it has been changed
            if (! is_null($node)) {
                $this->record->scope = $node->scope;
                $formerNextSibling = null;
                if ($this->record->exists) {
                    $formerNextSibling = $this->record->getNextSibling();
                }
                if ((is_null($formerNextSibling) || ($formerNextSibling->id != $node->id))) {
                    $this->record->beforeNode($node);
                }
            }
        }

        // If there is no previous or next sibling, try to check if we need to append / prepend it to the root node
        if (is_null($node)) {
            $node = Category::find($this->request->get('parent_id'));
            $previousParent = $this->record->ancestors()
                ->get()
                ->last();
            if (! is_null($node) && ! is_null($previousParent) && $previousParent->id != $node->id) {
                $this->record->scope = $node->scope;
                $nextSibling = $this->record->getNextSibling();
                if (is_null($nextSibling)) {
                    $this->record->appendToNode($node);
                } else {
                    $this->record->prependToNode($node);
                }
            } elseif (! is_null($node) && is_null($previousParent)) {
                $this->record->scope = $node->scope;
                $this->record->appendToNode($node);
            }
        }

        if (! is_null($node)) {
            $this->record->scope = $node->scope;
        }
    }
}
