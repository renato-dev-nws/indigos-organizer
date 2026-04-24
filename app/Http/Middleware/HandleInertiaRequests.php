<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CloudStorageManager;
use App\Support\SystemSettingsRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\SystemSetting;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only(['id', 'name', 'email', 'theme', 'is_admin', 'is_super_admin', 'avatar_url', 'push_enabled', 'email_enabled', 'whatsapp_enabled', 'whatsapp_phone', 'notification_preferences']),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => app()->isProduction()
                    ? preg_replace('/^http:\/\//i', 'https://', (string) $request->fullUrl())
                    : $request->fullUrl(),
            ],
            'systemSettings' => fn () => [
                'logo_url' => ($logoPath = SystemSetting::get('logo_path'))
                    ? asset('storage/'.$logoPath)
                    : null,
                'icon_url' => ($iconPath = SystemSetting::get('icon_path'))
                    ? asset('storage/'.$iconPath)
                    : null,
                'module_colors' => SystemSettingsRegistry::moduleColors(),
                'cloud_integrations' => [
                    'google' => CloudStorageManager::integrationStatus('google'),
                    'dropbox' => CloudStorageManager::integrationStatus('dropbox'),
                ],
            ],
            'vapidPublicKey' => fn () => config('webpush.vapid.public_key'),
            'unreadNotificationsCount' => fn () => $user?->unreadNotifications()->count() ?? 0,
        ];
    }
}
