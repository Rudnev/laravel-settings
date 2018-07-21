## Laravel Settings


[![Build Status](https://travis-ci.org/Rudnev/laravel-settings.svg?branch=master)](https://travis-ci.org/Rudnev/laravel-settings)

Persistent settings for Laravel Framework 

+ Easy to Use
+ Events
+ Cache
+ Extendable Settings Manager

### Requirements

* PHP 7.1 or higher
* Laravel 5.5 or higher

### Basic usage

Retrieve and store data using the global `settings` function:
```php
settings(['foo' => 'bar']);
settings('foo');

// the same:

settings()->set('foo', 'bar');
settings()->get('foo');

```

You can also use the `Settings` facade:
```php
Settings::set('foo', 'bar');
Settings::get('foo');
```

And if you prefer contracts, you can use Method dependency injection:
```php
<?php

namespace App\Http\Controllers;

use Rudnev\Settings\Contracts\RepositoryContract as Settings;

class MyController extends Controller
{
    public function index(Settings $settings)
    {
        $value = $settings->get('foo');
    }
}
```

### Installation

You can install the package via composer:

``` bash
composer require rudnev/laravel-settings
```

Publish [the config](config/settings.php) file with:

```bash
php artisan vendor:publish --provider="Rudnev\Settings\SettingsServiceProvider" --tag="config"
```

Publish [the migration](database/migrations/create_settings_table.stub) with:

```bash
php artisan vendor:publish --provider="Rudnev\Settings\SettingsServiceProvider" --tag="migrations"
```

If you're using a database with JSON support (such as MySQL >= 5.7 or PostgreSQL) you can update the `create_settings_tables.php` migration and replace: 

```php
$table->text($this->value)->nullable();
```

with:

```php
$table->json($this->value)->nullable();
```

or replace with the `jsonb` (preferably if supported):

```php
$table->jsonb($this->value)->nullable();
```

After the migration has been published you can create the settings table by running the migrations:

```bash
php artisan migrate
```

### API

#### Check the value
Determine if an item exists in the settings store.
```php
Settings::has('foo');
```
#### Get the value
Retrieve an item from the settings store by key.
```php
Settings::get('foo');

// You can specify a default value when an property is null or not found:

Settings::get('foo', 'default'); 
```

#### Get multiple values
Retrieve multiple items from the settings store by key.
Items not found in the settings store will have a null value.
```php
Settings::get(['foo', 'bar', 'baz']);

// with default value:

Settings::get(['foo', 'bar', 'baz' => 'default']);
```
#### Get all values
Get all of the settings items.
```php
Settings::all();
```
#### Set the value
Store an item in the settings store.
```php
Settings::set('foo', 'bar');
```
#### Set multiple values
Store multiple items in the settings store.
```php
Settings::set([
    'foo' => 'bar', 
    'qux' => 'baz'
]);
```
#### Remove the value
Remove an item from the settings store.
```php
Settings::forget('foo');
```
#### Remove multiple values
Remove multiple items from the settings store.
```php
Settings::forget(['foo', 'bar']);
```
#### Remove all values
Remove all items from the settings store.
```php
Settings::flush();
```

#### Dot Notation

You can use dot syntax to indicate the item depth:

```php
Settings::set('products.desk', ['price' => 300, 'height' => 100]);
Settings::forget('products.desk.price');
Settings::has('products.desk.height');
Settings::get('products.desk');
```

### Events

Events are triggered if this is not disabled via config (enabled by default).


| Event | Description |
| --- | --- |
| [\Rudnev\Settings\Events\PropertyWritten](src/Events/PropertyWritten.php) | Fired after the value is written to the store.
| [\Rudnev\Settings\Events\PropertyReceived](src/Events/PropertyReceived.php) | Fired after the value is retrieved from the store.
| [\Rudnev\Settings\Events\PropertyMissed](src/Events/PropertyMissed.php) | Fired if the item not found or is not set.
| [\Rudnev\Settings\Events\PropertyRemoved](src/Events/PropertyRemoved.php) | Fired after the item is removed from the store.
| [\Rudnev\Settings\Events\AllSettingsReceived](src/Events/AllSettingsReceived.php) | Fired after retrieving all items from the store.
| [\Rudnev\Settings\Events\AllSettingsRemoved](src/Events/AllSettingsRemoved.php) | Fired after removing all items from the store.

### Custom Store

To create your own store, you must use the `extend` method on the `SettingsManager`, which is used to bind a custom driver resolver to the manager, but first you need implement the
[StoreContract](src/Contracts/StoreContract.php):

```php
use Rudnev\Settings\Contracts\StoreContract;

class MongoStore implements StoreContract {
// ...
}
```

After that, to register a new settings driver named "mongo", you would do the following:

```php
Settings::extend('mongo', function($app)
{
    return Settings::repository(new MongoStore);
});
```

The first argument passed to the extend method is the name of the driver. This will correspond to your driver option in the `config/settings.php` configuration file. The second argument is a Closure that should return an `Rudnev\Settings\Repository` instance. The Closure will be passed an `$app` instance, which is an instance of `Illuminate\Foundation\Application` and a service container.

The call to `Settings::extend` could be done in the `boot` method of the default  `App\Providers\AppServiceProvider` that ships with fresh Laravel applications, or you may create your own service provider to house the extension.

Finally, you can access your store as follows:

```php
Settings::store('mongo')->get('foo');
```

## Credits

- [Andrei Rudnev](https://github.com/Rudnev)

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.