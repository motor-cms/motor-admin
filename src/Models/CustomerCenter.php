<?php

namespace Motor\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Motor\Admin\Database\Factories\ClientFactory;
use Motor\Core\Traits\Filterable;
use Motor\Core\Traits\Searchable;

/**
 * Motor\Admin\Models\CustomerCenter
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $address
 * @property string $zip
 * @property string $city
 * @property string $country_iso_3166_1
 * @property string gmaps_url
 * @property string phone
 * @property string fax
 * @property string email
 * @property string latitude
 * @property string opening_times
 * @property string longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 */
class CustomerCenter extends Model
{
    use Searchable;
    use Filterable;
    use HasFactory;

    /**
     * Searchable columns for the searchable trait
     *
     * @var array
     */
    protected array $searchableColumns = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'address',
        'zip',
        'city',
        'country_iso_3166_1',
        'gmaps_url',
        'phone',
        'fax',
        'email',
        'latitude',
        'longitude',
        'opening_times',
    ];

}
