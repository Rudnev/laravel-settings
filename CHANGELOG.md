# Changelog

All notable changes to `laravel-settings` will be documented in this file

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
