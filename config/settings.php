<?php

return [
    /*
   |--------------------------------------------------------------------------
   | Default Settings Store
   |--------------------------------------------------------------------------
   |
   | This option controls the default connection that gets used while
   | using this library. This connection is used when another is
   | not explicitly specified when executing a given settings function.
   |
   | Supported: "array", "database"
   |
   */

    'default' => env('SETTINGS_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Settings Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the settings "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same settings driver to group types of items stored in your settings store.
    |
    */

    'stores' => [

        'array' => [
            # The driver name.
            'driver' => 'array',
        ],

        'database' => [
            # The driver name.
            'driver' => 'database',

            # The database connection from file "config/database.php".
            # If set to null or false, the default connection will be used.
            'connection' => null,

            /*
             |------------------------------------------------------------------
             |  Cache configuration:
             |------------------------------------------------------------------
             */
            'cache' => [
                # Enable / Disable caching.
                'enabled' => true,

                # Time to Live in minutes.
                'ttl' => 120,

                # The cache store from file "config/cache.php".
                # If set to null or false, the default store will be used.
                'store' => null,
            ],

            /*
             |------------------------------------------------------------------
             |  Names of tables and columns.
             |------------------------------------------------------------------
             */
            'names' => [
                'settings' => [
                    # The name of the table for storing app settings.
                    'table' => 'settings',

                    # The name of the scope column.
                    'scope' => 'scope',

                    # The name of the key column.
                    'key' => 'name',

                    # The name of the value column.
                    'value' => 'value',
                ],

                'settings_models' => [
                    # The name of the table for storing model settings.
                    'table' => 'settings_models',

                    # The prefix for polymorphic association.
                    'entity' => 'model',

                    # The name of the key column.
                    'key' => 'name',

                    # The name of the value column.
                    'value' => 'value',
                ],
            ],
        ],

        // Place the config for your custom store here:

        // ...

    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable / Disable events triggering.
    |
    */

    'events' => true,
];