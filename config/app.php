<?php
/**
 * @author Sergei Melnikov <me@rnr.name>
 */

return [
    'timezone' => 'UTC',
    'log' => 'daily',
    'providers' => [
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class
    ],
    'aliases' => [
        'Schema' => Illuminate\Support\Facades\Schema::class
    ]
];