<?php

namespace App\Providers;

use App\Models\Content;
use App\Models\Idea;
use App\Models\Plan;
use App\Models\Task;
use App\Observers\ContentObserver;
use App\Observers\IdeaObserver;
use App\Observers\PlanObserver;
use App\Observers\TaskObserver;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDriveService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        Storage::extend('google', function ($app, $config) {
            $client = new GoogleClient();
            $client->setClientId($config['clientId'] ?? null);
            $client->setClientSecret($config['clientSecret'] ?? null);

            if (! empty($config['refreshToken'] ?? null)) {
                $client->refreshToken($config['refreshToken']);
            }

            $service = new GoogleDriveService($client);

            $options = [];
            if (! empty($config['teamDriveId'] ?? null)) {
                $options['teamDriveId'] = $config['teamDriveId'];
            }
            if (! empty($config['sharedFolderId'] ?? null)) {
                $options['sharedFolderId'] = $config['sharedFolderId'];
            }

            $adapter = new GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
            $driver = new Filesystem($adapter, $config);

            return new FilesystemAdapter($driver, $adapter, $config);
        });

        Storage::extend('dropbox', function ($app, $config) {
            $token = (string) ($config['authorizationToken'] ?? '');
            $client = new DropboxClient($token);
            $adapter = new DropboxAdapter($client);
            $driver = new Filesystem($adapter, $config);

            return new FilesystemAdapter($driver, $adapter, $config);
        });

        Content::observe(ContentObserver::class);
        Plan::observe(PlanObserver::class);
        Task::observe(TaskObserver::class);
        Idea::observe(IdeaObserver::class);

        Vite::prefetch(concurrency: 3);
    }
}
