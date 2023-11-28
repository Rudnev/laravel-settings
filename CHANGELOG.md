# Changelog

All notable changes to `laravel-settings` will be documented in this file

## 2.3.1 - 2023-11-28
- Fixed cache TTL for PHP >= 8.0

## 2.3.0 - 2023-02-19
- Added Laravel 10.x support

## 2.2.2 - 2022-08-12
- Fixed inconsistent cache for preloaded scopes when using Laravel Octane

## 2.2.1 - 2022-03-05
- Fixed deprecation notices in PHP 8.1

## 2.2.0 - 2022-02-21
- Added Laravel 9.0 support

## 2.1.1 - 2021-10-08
- Setting a new application instance to the `SettingsManager` by `Laravel\Octane\Events\RequestReceived` event

## 2.1.0 - 2021-10-07
- Added Laravel Octane support
- Fixed possible memory leaks

## 2.0.0 - 2021-10-05
- The `Repository::all()` method now returns default values
- Now the "morph map" is used to save the polymorphic relation instead of fully-qualified class name (if [configured](https://laravel.com/docs/8.x/eloquent-relationships#custom-polymorphic-types) in the application).

## 1.5.1 - 2021-10-03
- The `Container::toArray()` and `Container::toJson()` methods now returns default values

## 1.5.0 - 2020-12-31
- Added Laravel 8.0 support
- Added PHP 8.0 support

## 1.4.0 - 2020-05-02
- Added Laravel 7.0 support

## 1.3.0 - 2019-12-31
- Added Laravel 6.0 support
- Fixes in the `HasSettings` trait

## 1.2.0 - 2019-03-20
- Added Laravel 5.8 support

## 1.1.0 - 2018-09-30
- Caching has been redesigned to cache all scopes, not just the default scope.
- The default scope is changed to the value `default` instead of the empty string.
- The default scope can be changed in the `config/settings.php` file.
- Added new console command to clear the cache: `php artisan settings:cache-clear`.
- Now you can specify the scopes, all the items of which will be loaded with a single db-query 
when you first access the settings store. This can be done in the `config/settings.php` file, 
in the section `stores.database.scopes.preload`.

## 1.0.0 - 2018-09-17

Initial release.
