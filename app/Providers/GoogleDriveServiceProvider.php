<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class GoogleDriveServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        Storage::extend('google', function ($app, array $config) {
            $client = new Client();

            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            $service = new Drive($client);

            $adapter = new GoogleDriveAdapter(
                $service,
                $config['folder'] ?? '/'
            );

            $filesystem = new Filesystem($adapter);

            return new FilesystemAdapter(
                $filesystem,
                $adapter,
                $config
            );
        });
    }
}
