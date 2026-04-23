<?php

namespace App\Support;

use App\Models\Content;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CloudStorageManager
{
    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_DROPBOX = 'dropbox';

    public const PROVIDERS = [self::PROVIDER_GOOGLE, self::PROVIDER_DROPBOX];

    public static function integrationStatus(string $provider): array
    {
        $provider = self::normalizeProvider($provider);

        return [
            'configured' => self::isConfigured($provider),
            'account_name' => SystemSetting::get(self::key($provider, 'account_name')),
            'account_email' => SystemSetting::get(self::key($provider, 'account_email')),
            'base_folder' => SystemSetting::get(self::key($provider, 'base_folder'), 'indigos-organizer'),
        ];
    }

    public static function isConfigured(string $provider): bool
    {
        $provider = self::normalizeProvider($provider);

        if ($provider === self::PROVIDER_GOOGLE) {
            return filled(SystemSetting::get(self::key($provider, 'refresh_token')));
        }

        return filled(SystemSetting::get(self::key($provider, 'refresh_token'))) || filled(SystemSetting::get(self::key($provider, 'access_token')));
    }

    public static function saveConnection(string $provider, array $payload): void
    {
        $provider = self::normalizeProvider($provider);

        foreach (['access_token', 'refresh_token', 'token_expires_at', 'account_name', 'account_email'] as $field) {
            if (array_key_exists($field, $payload)) {
                SystemSetting::set(self::key($provider, $field), $payload[$field]);
            }
        }

        if (! filled(SystemSetting::get(self::key($provider, 'base_folder')))) {
            SystemSetting::set(self::key($provider, 'base_folder'), 'indigos-organizer');
        }
    }

    public static function disconnect(string $provider): void
    {
        $provider = self::normalizeProvider($provider);

        foreach (['access_token', 'refresh_token', 'token_expires_at', 'account_name', 'account_email'] as $field) {
            SystemSetting::set(self::key($provider, $field), null);
        }
    }

    public static function updateBaseFolder(string $provider, string $folder): void
    {
        $provider = self::normalizeProvider($provider);
        SystemSetting::set(self::key($provider, 'base_folder'), trim($folder));
    }

    public static function configureDisk(string $provider): void
    {
        $provider = self::normalizeProvider($provider);

        if ($provider === self::PROVIDER_GOOGLE) {
            Config::set('filesystems.disks.google.clientId', (string) config('services.google.client_id'));
            Config::set('filesystems.disks.google.clientSecret', (string) config('services.google.client_secret'));
            Config::set('filesystems.disks.google.refreshToken', (string) SystemSetting::get(self::key($provider, 'refresh_token')));
            Config::set('filesystems.disks.google.folder', SystemSetting::get(self::key($provider, 'base_folder'), 'indigos-organizer'));
            return;
        }

        if ($provider === self::PROVIDER_DROPBOX) {
            $accessToken = self::resolveDropboxAccessToken();
            Config::set('filesystems.disks.dropbox.authorizationToken', $accessToken);
            Config::set('filesystems.disks.dropbox.root', SystemSetting::get(self::key($provider, 'base_folder'), 'indigos-organizer'));
        }
    }

    public static function testConnection(string $provider): void
    {
        self::configureDisk($provider);
        Storage::disk($provider)->files('');
    }

    public static function buildContentUploadPath(Content $content, string $originalName): string
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

    public static function listTreeNodes(string $provider, string $path = ''): array
    {
        self::configureDisk($provider);

        $normalizedPath = trim($path, '/');
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($provider);

        $directories = collect($disk->directories($normalizedPath))
            ->map(fn (string $directory) => [
                'key' => $directory,
                'label' => basename($directory),
                'leaf' => false,
                'icon' => 'pi pi-folder',
                'type' => 'folder',
                'data' => [
                    'path' => $directory,
                    'provider' => $provider,
                    'kind' => 'folder',
                ],
            ]);

        $files = collect($disk->files($normalizedPath))
            ->map(function (string $file) use ($disk, $provider) {
                $size = null;
                $mimeType = null;

                try {
                    $size = $disk->size($file);
                } catch (\Throwable) {
                    $size = null;
                }

                try {
                    $mimeType = $disk->mimeType($file);
                } catch (\Throwable) {
                    $mimeType = null;
                }

                return [
                    'key' => $file,
                    'label' => basename($file),
                    'leaf' => true,
                    'icon' => 'pi pi-file',
                    'type' => 'file',
                    'data' => [
                        'path' => $file,
                        'provider' => $provider,
                        'kind' => 'file',
                        'original_name' => basename($file),
                        'mime_type' => $mimeType,
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

    private static function resolveDropboxAccessToken(): ?string
    {
        $provider = self::PROVIDER_DROPBOX;
        $accessToken = SystemSetting::get(self::key($provider, 'access_token'));
        $expiresAt = SystemSetting::get(self::key($provider, 'token_expires_at'));
        $isExpired = false;

        if (filled($expiresAt)) {
            try {
                $isExpired = Carbon::parse($expiresAt)->isPast();
            } catch (\Throwable) {
                $isExpired = true;
            }
        }

        if (filled($accessToken) && (! filled($expiresAt) || ! $isExpired)) {
            return $accessToken;
        }

        $refreshToken = SystemSetting::get(self::key($provider, 'refresh_token'));
        if (! filled($refreshToken)) {
            return $accessToken;
        }

        $response = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('services.dropbox.client_id'),
            'client_secret' => config('services.dropbox.client_secret'),
        ]);

        if (! $response->successful()) {
            return $accessToken;
        }

        $data = $response->json();
        $newAccessToken = $data['access_token'] ?? $accessToken;
        $newExpiresAt = isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in'])->toDateTimeString() : $expiresAt;

        SystemSetting::set(self::key($provider, 'access_token'), $newAccessToken);
        SystemSetting::set(self::key($provider, 'token_expires_at'), $newExpiresAt);

        return $newAccessToken;
    }

    private static function key(string $provider, string $field): string
    {
        return "cloud_{$provider}_{$field}";
    }

    private static function normalizeProvider(string $provider): string
    {
        $provider = trim(Str::lower($provider));
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return $provider;
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
}
