<?php

namespace App\Support;

use App\Models\Content;
use App\Models\UserCloudConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CloudStorageManager
{
    public static function configureDisk(UserCloudConnection $connection): void
    {
        $source = $connection->provider;

        if ($source === UserCloudConnection::PROVIDER_GOOGLE) {
            Config::set('filesystems.disks.google.clientId', (string) config('services.google.client_id'));
            Config::set('filesystems.disks.google.clientSecret', (string) config('services.google.client_secret'));
            Config::set('filesystems.disks.google.refreshToken', $connection->refresh_token);
            Config::set('filesystems.disks.google.folder', $connection->base_folder ?: 'ERP_Arquivos');
            return;
        }

        if ($source === UserCloudConnection::PROVIDER_DROPBOX) {
            $accessToken = self::resolveDropboxAccessToken($connection);
            Config::set('filesystems.disks.dropbox.authorizationToken', $accessToken);
            Config::set('filesystems.disks.dropbox.root', $connection->base_folder ?: 'ERP_Arquivos');
        }
    }

    public static function testConnection(UserCloudConnection $connection): void
    {
        self::configureDisk($connection);
        Storage::disk($connection->provider)->files('');
    }

    public static function buildContentUploadPath(UserCloudConnection $connection, Content $content, string $originalName): string
    {
        $moduleFolder = 'Conteudos';
        $contentFolder = self::sanitizePathSegment($content->title ?: 'Sem titulo');
        $filename = self::sanitizeFileName($originalName);

        return trim(implode('/', [
            'indigos-organizer',
            $moduleFolder,
            $contentFolder,
            $filename,
        ]), '/');
    }

    public static function listTreeNodes(UserCloudConnection $connection, string $path = ''): array
    {
        self::configureDisk($connection);

        $normalizedPath = trim($path, '/');
        $disk = Storage::disk($connection->provider);

        $directories = collect($disk->directories($normalizedPath))
            ->map(fn (string $directory) => [
                'key' => $directory,
                'label' => basename($directory),
                'children' => [],
                'leaf' => false,
                'icon' => 'pi pi-folder',
                'type' => 'folder',
                'data' => [
                    'path' => $directory,
                    'provider' => $connection->provider,
                    'kind' => 'folder',
                ],
            ]);

        $files = collect($disk->files($normalizedPath))
            ->map(function (string $file) use ($disk, $connection) {
                $size = null;

                try {
                    $size = $disk->size($file);
                } catch (\Throwable) {
                    $size = null;
                }

                return [
                    'key' => $file,
                    'label' => basename($file),
                    'leaf' => true,
                    'icon' => 'pi pi-file',
                    'type' => 'file',
                    'data' => [
                        'path' => $file,
                        'provider' => $connection->provider,
                        'kind' => 'file',
                        'original_name' => basename($file),
                        'mime_type' => null,
                        'size' => $size,
                    ],
                ];
            });

        return $directories
            ->concat($files)
            ->sortBy(fn (array $node) => sprintf('%s-%s', $node['type'] === 'folder' ? '0' : '1', Str::lower($node['label'])))
            ->values()
            ->all();
    }

    private static function sanitizePathSegment(string $value): string
    {
        $sanitized = Str::of($value)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9\s-]/', ' ')
            ->squish()
            ->trim(' .')
            ->toString();

        return $sanitized !== '' ? $sanitized : 'Sem titulo';
    }

    private static function sanitizeFileName(string $value): string
    {
        $extension = pathinfo($value, PATHINFO_EXTENSION);
        $name = pathinfo($value, PATHINFO_FILENAME);
        $sanitized = self::sanitizePathSegment($name);

        return $extension !== '' ? $sanitized.'.'.$extension : $sanitized;
    }

    private static function resolveDropboxAccessToken(UserCloudConnection $connection): ?string
    {
        if (filled($connection->access_token) && ($connection->token_expires_at?->isFuture() ?? true)) {
            return $connection->access_token;
        }

        if (blank($connection->refresh_token)) {
            return $connection->access_token;
        }

        $response = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
            'client_id' => config('services.dropbox.client_id'),
            'client_secret' => config('services.dropbox.client_secret'),
        ]);

        if (! $response->successful()) {
            return $connection->access_token;
        }

        $data = $response->json();
        $connection->update([
            'access_token' => $data['access_token'] ?? $connection->access_token,
            'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : $connection->token_expires_at,
        ]);

        return $connection->fresh()->access_token;
    }
}
