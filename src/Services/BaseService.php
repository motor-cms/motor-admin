<?php

namespace Motor\Admin\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Motor\Core\Filter\Filter;
use Motor\Core\Filter\Renderers\PerPageRenderer;
use Motor\Core\Filter\Renderers\SortRenderer;
use Motor\Core\Filter\Renderers\SearchRenderer;
use Spatie\MediaLibrary\HasMedia;

/**
 * Class BaseService
 */
abstract class BaseService
{
    protected $filter;

    protected $request;

    protected $model;

    protected $record;

    protected array $data = [];

    protected $result;

    protected string $sortableField = 'id';

    protected string $sortableDirection = 'ASC';

    /**
     * Basic create method.
     * Usually called by an API
     *
     * @return \Motor\Admin\Services\BaseService
     */
    public static function create(Request|array $request): BaseService
    {
        return (new static())->setRequest($request)
            ->doCreate();
    }

    /**
     * Basic update method.
     * Usually called by an API
     *
     * @return \Motor\Admin\Services\BaseService
     */
    public static function update(Model $record, Request|array $request): BaseService
    {
        return (new static())->setRequest($request)
            ->setRecord($record)
            ->doUpdate();
    }

    /**
     * Simple wrapper to return the given record
     */
    public static function show($record): mixed
    {
        return (new static())->setRecord($record)
            ->doShow();
    }

    /**
     * Wrapper to return paginated results
     * Applies basic filters and adds filters through the individual services filters() method
     *
     * @param  string  $alias
     * @param  null  $sorting
     */
    public static function collection($alias = '', $sorting = null): BaseService
    {
        $instance = new static();
        $instance->filter = new Filter($alias);
        $instance->defaultFilters();
        $instance->filters();

        if (Arr::get($_GET, 'sortable_field') && Arr::get($_GET, 'sortable_direction')) {
            $instance->setSorting([
                Arr::get($_GET, 'sortable_field'),
                Arr::get($_GET, 'sortable_direction'),
            ]);
        }

        //		if (!is_null($sorting))
        //		{
        //			$instance->setSorting($sorting);
        //		}

        return $instance;
    }

    /**
     * Simple wrapper around the delete method of the record
     */
    public static function delete($record): mixed
    {
        return (new static())->setRecord($record)
            ->doDelete();
    }

    /**
     * Sets default filters to use with the collection() method
     */
    public function defaultFilters()
    {
        $this->filter->add(new SearchRenderer('search'));
        $this->filter->add(new SortRenderer('sort'));
        $this->filter->add(new PerPageRenderer('per_page'))
            ->setup();
    }

    /**
     * Returns the filter class
     * Usually necessary to get filters to the grid when displaying a collection
     *
     * @return mixed
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * Returns the result of create/update/delete/record methods
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Returns the paginator for the model
     */
    public function getPaginator(): mixed
    {
        $query = ($this->model)::filteredByMultiple($this->getFilter());
        $query = $this->applyScopes($query);
        $query = $this->applySorting($query);

        return $query->paginate($this->getFilter()
            ->get('per_page')
            ->getValue());
    }

    /**
     * Set sorting array
     *
     * @return $this
     */
    public function setSorting(array $sorting): static
    {
        [$this->sortableField, $this->sortableDirection] = $sorting;

        return $this;
    }

    /**
     * Add custom sorting, if available
     */
    public function applySorting($query): mixed
    {
        // check if we need to join a table
        $join = false;
        if (strpos($this->sortableField, '.') > 0) {
            [$table, $field] = explode('.', $this->sortableField);
            $tableColumn = $table.'_id';

            // Handle blamable
            if ($table == 'createdBy' || $table == 'updatedBy' || $table == 'deletedBy') {
                $tableColumn = Str::snake($table);
                $table = 'user';
                $this->sortableField = $table.'.'.$field;
            }
            $join = true;
            $joinExists = false;

            $joins = $query->query->joins;
            if ($joins == null) {
                $joinExists = false;
            } else {
                foreach ($joins as $join) {
                    if ($join->table == $table) {
                        $joinExists = true;
                    }
                }
            }

            if (! $joinExists) {
                $query->join(Str::plural($table).' as '.$table, $tableColumn, $table.'.id');
            }
        }

        if (! is_null($this->sortableField)) {
            if ($join) {
                return $query->orderBy($this->sortableField, $this->sortableDirection);
            }

            // Checking if we're using Eloquent Builder, which has a getModel() method or the Scout builder, which only has a model property
            $model = isset($query->model) ? $query->model : $query->getModel();

            return $query->orderBy(
                $this->sortableField, $this->sortableDirection);

        }

        return $query;
    }

    /**
     * Add custom scopes to query
     */
    public function applyScopes($query): mixed
    {
        return $query;
    }

    /**
     * Show the record (set result to the current record)
     * Also invokes before and after methods
     *
     * @return $this
     */
    public function doShow(): static
    {
        $this->beforeShow();
        $this->result = $this->record;
        $this->afterShow();

        return $this;
    }

    /**
     * Creates a record and sets the result to the record when successful, or false when unsuccessful
     * Also invokes before and after methods
     *
     * @return $this
     */
    public function doCreate(): static
    {
        $this->record = new $this->model();
        $this->beforeCreate();
        $this->record->fill($this->data);
        $this->result = $this->record->save();
        $this->afterCreate();
        if ($this->result) {
            $this->result = $this->record->fresh();
        }

        return $this;
    }

    /**
     * Updates a record and sets the result to the record when successful, or false when unsuccessful
     * Also invokes before and after methods
     *
     * @return $this
     */
    public function doUpdate(): static
    {
        $this->beforeUpdate();
        $this->result = $this->record->update($this->data);
        $this->afterUpdate();
        if ($this->result) {
            $this->result = $this->record->fresh();
        }

        return $this;
    }

    /**
     * Deletes a record and sets the result to either true or false
     * Also invokes before and after methods
     *
     * @return $this
     */
    public function doDelete(): static
    {
        $this->beforeDelete();
        if ($this->record->exists) {
            $this->result = $this->record->delete();
        }
        $this->afterDelete();

        return $this;
    }

    /**
     * Sets a record
     *
     * @return $this
     */
    public function setRecord(Model $record): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Sets a request object
     *
     * @return $this
     */
    public function setRequest(Request|array $request): static
    {
        $key = '';

        $this->request = $request;
        if (is_array($request)) {
            $this->data = $request;

            return $this;
        }

        if ($key == '') {
            $this->data = $this->request->all();
        } else {
            $this->data = $this->request->input($key, []);
        }

        return $this;
    }

    /**
     * Handles file uploads either with a UploadedFile object or a base64 encoded file
     *
     * @param  null  $collection
     * @param  null  $record
     * @param  false  $addToCollection
     * @return $this
     *
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function uploadFile(
        $file,
        string $identifier = 'image',
        $collection = null,
        $record = null,
        bool $addToCollection = false
    ): static {
        if (! is_null($record) && ! $record instanceof HasMedia) {
            return $this;
        }

        if (is_null($record)) {
            $record = $this->record;
        }

        if (! $record instanceof HasMedia) {
            return $this;
        }

        $collection = (! is_null($collection) ? $collection : $identifier);

        // Delete from API
        if (Arr::get($this->data, $identifier.'.dataUrl') !== null || Arr::get($this->data, $identifier) === false) {
            $record->clearMediaCollection($identifier);
            if (! is_null($collection)) {
                $record->clearMediaCollection($collection);
            }
        }

        if ((! is_null($file) && $this->isValidBase64(Arr::get($this->data, $identifier.'.dataUrl'))) && $addToCollection === false) {
            $record->clearMediaCollection($identifier);
            if (! is_null($collection)) {
                $record->clearMediaCollection($collection);
            }
        }

        if ($file instanceof UploadedFile && $file->isValid()) {
            $record->addMedia($file)
                ->toMediaCollection($collection, 'media');
        } else {
            if ($this->isValidBase64(Arr::get($this->data, $identifier.'.dataUrl'))) {
                $image = base64_decode(Arr::get($this->data, $identifier.'.dataUrl'));

                $tempFilename = tempnam(sys_get_temp_dir(), 'upload');

                $name = Arr::get($this->data, $identifier.'.name', $tempFilename);

                $handle = fopen($tempFilename, 'w');
                fwrite($handle, $image);
                fclose($handle);
                $record->addMedia($tempFilename)
                    ->setName($name)
                    ->setFileName($name)
                    ->toMediaCollection($collection, 'media');
            }
        }

        return $this;
    }

    /**
     * Helper method to check if a file upload field is base64 encoded
     */
    protected function isValidBase64($string): bool
    {
        $decoded = base64_decode($string, true);
        // Check if there is no invalid character in strin
        if (! preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) {
            return false;
        }

        // Decode the string in strict mode and send the responce
        if (! base64_decode($string, true)) {
            return false;
        }

        // Encode and compare it to origional one
        if (base64_encode($decoded) != $string) {
            return false;
        }

        return true;
    }

    /**
     * Stub for the filters method of the child class
     */
    public function filters()
    {
    }

    /**
     * Stub for the beforeCreate method of the child class
     */
    public function beforeCreate()
    {
    }

    /**
     * Stub for the afterCreate method of the child class
     */
    public function afterCreate()
    {
    }

    /**
     * Stub for the beforeUpdate method of the child class
     */
    public function beforeUpdate()
    {
    }

    /**
     * Stub for the afterUpdate method of the child class
     */
    public function afterUpdate()
    {
    }

    /**
     * Stub for the beforeDelete method of the child class
     */
    public function beforeDelete()
    {
    }

    /**
     * Stub for the afterDelete method of the child class
     */
    public function afterDelete()
    {
    }

    /**
     * Stub for the beforeShow method of the child class
     */
    public function beforeShow()
    {
    }

    /**
     * Stub for the afterShow method of the child class
     */
    public function afterShow()
    {
    }
}
