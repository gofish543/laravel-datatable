
# Datatable Laravel 7+
Package for converting a request into a datatable response with searching, sorting, filtering, pagination, and data minimization in Laravel 7+. 

# Setup

## Composer

Pull this package in through Composer
```sh
composer require dykhuizen/laravel-datatable
```

# Usage

Use **Datatable** trait inside your *Eloquent* model(s). 
This single trait gives you access the following traits:
* Sortable - Applies a list of order by queries for specified columns
* Searchable - Applies a search query for selected columns
* Filterable - Applies a list of filters to the query based on columns selected
* Paginateable - Either runs the `$eloquent->get()` or `$eloquent->paginate()` methods based on the request
* Selectable - Selects the response data to be returned


```php
use Dykhuizen\Datatable;

use Illuminate\Database\Eloquent\Model;
use Dykhuizen\Datatable\Datatable;

class User extends Model 
{
    use Datatable;
}
```
```http request
GET http://localhost:8080/api/users
sortColumns=name,id& 
sortOrder=asc,desc& 
searchColumns=name&
search=testingName&
page=1&
per_page=5&
filter=deleted_at&
filter_deleted_at=false&
selectableFields=id,name,role.name
```

## Sortable
The sortable columns and order default to the keys `sortColumns` and `sortOrder` respectively.
<br>
These keys can be overwritten on a per model instance by setting the `$sortableColumnsKey` and `$sortableOrderKey` variables.
<br>
The values are expected to be comma delimited values and work with `HasOne` and `BelongsTo` relations
<br>
<br>
Ex:
```
sortColumns = 'id,username,role.id'
sortOrder = 'asc,desc,desc'
```
If you wish to have a field ordered which does not exist within a model's fields, you can optionally define a `[property]Sortable` function
<br>
Below is an address example
```php
public function addressSortable($query, $direction)
{
    return $query->join('profiles', 'users.id', '=', 'profiles.user_id')->orderBy('address', $direction)->select('users.*');
}
``` 

## Searchable
The searchable columns and search default to the keys `searchColumns` and `search` respectively.
<br>
These keys can be overwritten on a per model instance by setting the `$searchableColumnsKey` and `$searchableSearchKey` variables.
<br>
The values are expected to be comma delimited values and work with any type of relation
<br>
<br>
Ex:
```
searchColumns = 'username,profile.address'
search = 'phrase to search on'
```

If you wish to have a field searched which does not exist within a model's fields, you can optionally define a `[property]Searchable` function
<br>
Below is an address example
```php
public function addressSearchable($query, $search)
{
    return $query->whereHas('profile', function($query) use ($search)
    {
        return $query->where('address', '=', $search);
    });
}
```

## Filterable
The filterable column defaults to the key `filter`
<br>
This key can be overwritten on a per model instance by setting the `$filterableFieldsKey` variable.
<br>
The values are expected to be comma delimited values and work with any type of relation
Additionally, the filter values are expected to be passed as unique fields.
For example, filter on status expends the keys `filter = status` and `filter_status = 'yes,no,maybe` to exist
<br>
<br>
Ex:
```
filter = 'deleted_at,profile.deleted_at'
filter_deleted_at = 'false'
filter_profile.deleted_at = 'yes'
```

If you wish to have a field filtered which does not exist within a model's fields or has a unique filter attribute, you can optionally define a `[property]Filterable` function
<br>
Below is an address example
```php
public function addressFilterable($query, $filters)
{
    return $query->where(function($query) use($filters) {
        foreach($filters as $filter) {
            switch($filters) {
                case 'local':
                    $query->orWhere(...);
                    break;
                case 'foreign':
                    $query->orWhere(...);
                    break;
                default:
                    $query->orWhere(...);
                    break;
            }
        }
        
        return $query;
    });
}
```

## Paginateable
The paginateable columns default to `page` and `per_page` just like Laravel's built in pagination
<br>
These keys can be overwritten on a per model instance by setting the `$paginateablePageKey` and `$paginateablePerPageKey` variables.
<br>
If these keys exist, results will be paginated in the response.
If these keys to not exist, results will be returned via the `get()` method
<br>
<br>
Ex:
```
page = 1
per_page = 100
```

## Selectable
The selectable column defaults to `selectableFields`
<br>
This key can be overwritten on a per model instance by setting the `$selectableFieldsKey` variable
<br>
If this field exists, the selectable function will set a static property with these fields to be masked down to upon the call to `toArray()`
<br>
The goal of this trait is to reduce the response size when returning data to a front end application
<br>
For security reasons, the relation depth is maxed at 3 and if a relation is not loaded into the model before `toArray()` is called selectable will `abort()` out as someone is attempting to load relations dynamically which could cause data leakage
<br>
<br>
Ex:
```
selectableFields = id,profile.name,posts.id,posts.name
```