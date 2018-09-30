# Changelog

All notable changes to `laravel-settings` will be documented in this file

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