Laravel Settings
=============================
[![Build Status](https://app.travis-ci.com/Rudnev/laravel-settings.svg?branch=master)](https://app.travis-ci.com/Rudnev/laravel-settings)
[![StyleCI](https://github.styleci.io/repos/141638505/shield?branch=master&style=flat)](https://github.styleci.io/repos/141638505)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Laravel Octane Compatible](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://github.com/laravel/octane)

Persistent settings for Laravel Framework 

+ Easy to Use
+ App Settings + User Settings (Scopes)
+ Events
+ Cache
+ Extendable Settings Manager
+ [Laravel Octane](https://laravel.com/docs/octane) compatible

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

Getting started
=============================

[Installation](#installation)  
[API](#api)  
[Scopes](#scopes)  
[Translation](#translation)  
[Cache](#cache)  
[Events](#events)  
[Custom store](#custom-store)

----------------------------------

## Installation

You can install the package via composer:

``` bash
composer require rudnev/laravel-settings
```

Publish [the config](config/settings.php) file and [the migration](database/migrations/create_settings_table.stub) with:

```bash
php artisan vendor:publish --provider="Rudnev\Settings\ServiceProvider"
```


After the migration has been published you can create the settings table by running the migrations:

```bash
php artisan migrate
```

## API

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
Get all the settings items.
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

## Scopes

If you want to have settings for your model you can use `scope` method:

```php
$user = auth()->user();

Settings::scope($user)->set('lang', 'en');

Settings::scope($user)->get('lang');
```
 
But instead, it's better to use the `Rudnev\Settings\Traits\HasSettings` trait, for example:

```php
use Rudnev\Settings\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class User extends Model 
{
    use HasSettings;
    
    // ...
}
```

Now you can use `settings` property to set and get values:

```php
$user = new User();
$user->settings = ['lang' => 'en'];
$user->save();

echo $user->settings['lang'];

$user->settings['timezone'] = 'UTC';
$user->save();
```

Also, `settings` method provides direct access to the settings store (but model state will not be updated):

```php
$user->settings()->set('lang', 'en');
$user->settings()->get('lang');

// the same:

$user->settings(['lang' => 'en']);
$user->settings('lang');
```

To set the default settings define `$settingsConfig` property as array with `default` key:

```php
use Rudnev\Settings\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class User extends Model 
{
    use HasSettings;
    
    protected $settingsConfig = [
        'default' => [
            'timezone' => 'UTC'
        ]
    ];
    
    // ...
}
```

And if you want specify the store from `config/settings.php`, use `store` option:
```php
protected $settingsConfig = [
    'store' => 'database',
	
    'default' => [
        'timezone' => 'UTC'
    ]
];
```

In addition to the scopes for models, you can freely create scopes for your application settings, for example:
```php
// Set
Settings::scope('my-landing-page')->set('tracking_id', 'UA-000000-2');

// Get
Settings::scope('my-landing-page')->get('tracking_id');
```

## Translation

I suggest using the built-in laravel methods to translate any keys, names and descriptions. You can use this approach for most laravel packages, there is no need to store translations within different database tables when you can do this in one place.

For example, create a file `resources/lang/en/settings.php`:
```php
<?php

return [
    'user' => [
        'language' => [
            'name' => 'Language',
            'description' => 'The site language.'    
        ],
        
        'mfa' => [
            'name' => 'MFA',
            'description' => 'Multi-factor authentication.'    
        ],
    ]
];
```

And get translated strings:
```php
$user = auth()->user();
$userSettings = Settings::scope($user);

// Store locale:
$userSettings->set('language', 'en');

// Store some settings:
$userSettings->set('mfa', 'enabled');

// Retrieve locale:
$locale = $userSettings->get('language');

// Apply locale:
App::setLocale($locale);

// Print translated strings:
foreach ($userSettings->all() as $key => $value)
{
    $name = trans("settings.user.$key.name");
    $desc = trans("settings.user.$key.description");
    
    echo "$name: $value ($desc)" . PHP_EOL;
}
```

## Cache

Cache is enabled by default, you can change this in your `config/settings.php` file.

To clear the cache you can run the follow Artisan command: 

```bash
php artisan settings:clear-cache
```

## Events

Events are triggered if this is not disabled via config (enabled by default).


| Event | Description |
| --- | --- |
| [\Rudnev\Settings\Events\PropertyWritten](src/Events/PropertyWritten.php) | Fired after the value is written to the store.
| [\Rudnev\Settings\Events\PropertyReceived](src/Events/PropertyReceived.php) | Fired after the value is retrieved from the store.
| [\Rudnev\Settings\Events\PropertyMissed](src/Events/PropertyMissed.php) | Fired if the item not found or is not set.
| [\Rudnev\Settings\Events\PropertyRemoved](src/Events/PropertyRemoved.php) | Fired after the item is removed from the store.
| [\Rudnev\Settings\Events\AllSettingsReceived](src/Events/AllSettingsReceived.php) | Fired after retrieving all items from the store.
| [\Rudnev\Settings\Events\AllSettingsRemoved](src/Events/AllSettingsRemoved.php) | Fired after removing all items from the store.

## Custom Store

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
Settings::extend('mongo', function($app, $storeName, $config)
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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
