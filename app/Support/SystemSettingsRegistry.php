<?php

namespace App\Support;

use App\Models\SystemSetting;

class SystemSettingsRegistry
{
    public const CLOUD_PROVIDERS = ['google', 'dropbox'];

    public static function moduleDefinitions(): array
    {
        return [
            ['key' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'ph:squares-four-bold', 'default_color' => 'sky-500'],
            ['key' => 'tasks', 'title' => 'Tarefas', 'icon' => 'mdi:checkbox-multiple-outline', 'default_color' => 'indigo-500'],
            ['key' => 'calendar', 'title' => 'Calendario', 'icon' => 'mdi:calendar-month-outline', 'default_color' => 'cyan-500'],
            ['key' => 'ideas', 'title' => 'Ideias', 'icon' => 'mdi:lightbulb-multiple-outline', 'default_color' => 'violet-500'],
            ['key' => 'contents', 'title' => 'Conteudos', 'icon' => 'mdi:film-reel', 'default_color' => 'purple-500'],
            ['key' => 'plans', 'title' => 'Planejamentos', 'icon' => 'mdi:routes-clock', 'default_color' => 'emerald-500'],
            ['key' => 'fast_notes', 'title' => 'Notas Rapidas', 'icon' => 'mdi:notebook-edit-outline', 'default_color' => 'amber-500'],
            ['key' => 'events', 'title' => 'Eventos', 'icon' => 'ph:ticket-bold', 'default_color' => 'orange-500'],
            ['key' => 'venues', 'title' => 'Locais', 'icon' => 'mdi:map-marker-multiple-outline', 'default_color' => 'teal-500'],
            ['key' => 'contacts', 'title' => 'Contatos', 'icon' => 'ph:address-book-bold', 'default_color' => 'blue-500'],
            ['key' => 'shared_infos', 'title' => 'Informacoes Uteis', 'icon' => 'ph:info-bold', 'default_color' => 'rose-500'],
        ];
    }

    public static function moduleDefaultColors(): array
    {
        return collect(static::moduleDefinitions())
            ->mapWithKeys(fn (array $module) => [$module['key'] => $module['default_color']])
            ->all();
    }

    public static function moduleColors(): array
    {
        $defaults = static::moduleDefaultColors();

        return collect(static::moduleDefinitions())
            ->mapWithKeys(function (array $module) use ($defaults) {
                $key = $module['key'];

                return [$key => (string) SystemSetting::get("module_color_{$key}", $defaults[$key] ?? 'slate-500')];
            })
            ->all();
    }

    public static function tailwindColorPalette(): array
    {
        $families = [
            'slate',
            'gray',
            'zinc',
            'neutral',
            'stone',
            'red',
            'orange',
            'amber',
            'yellow',
            'lime',
            'green',
            'emerald',
            'teal',
            'cyan',
            'sky',
            'blue',
            'indigo',
            'violet',
            'purple',
            'fuchsia',
            'pink',
            'rose',
        ];

        $shades = ['400', '500', '600'];

        return collect($families)
            ->flatMap(fn (string $family) => collect($shades)->map(function (string $shade) use ($family) {
                $token = "{$family}-{$shade}";

                return [
                    'token' => $token,
                    'hex' => static::tokenToHex($token),
                    'label' => strtoupper($family).' '.$shade,
                ];
            }))
            ->values()
            ->all();
    }

    public static function tailwindColorTokens(): array
    {
        return collect(static::tailwindColorPalette())
            ->pluck('token')
            ->all();
    }

    public static function tokenToHex(string $token): string
    {
        return static::tailwindColorHexMap()[$token] ?? '#64748b';
    }

    public static function cloudIntegrations(): array
    {
        return [
            'google' => static::cloudIntegrationFor('google', 'Google Drive'),
            'dropbox' => static::cloudIntegrationFor('dropbox', 'Dropbox'),
        ];
    }

    public static function cloudIsConfigured(string $provider): bool
    {
        $prefix = "{$provider}_drive_";

        $clientId = (string) SystemSetting::get($prefix.'client_id', '');
        $clientSecret = (string) SystemSetting::get($prefix.'client_secret', '');
        $refreshToken = (string) SystemSetting::get($prefix.'refresh_token', '');

        if ($provider === 'dropbox') {
            return filled($refreshToken);
        }

        return filled($clientId) && filled($clientSecret) && filled($refreshToken);
    }

    private static function cloudIntegrationFor(string $provider, string $name): array
    {
        $prefix = "{$provider}_drive_";

        return [
            'name' => $name,
            'client_id' => (string) SystemSetting::get($prefix.'client_id', ''),
            'client_secret' => (string) SystemSetting::get($prefix.'client_secret', ''),
            'refresh_token' => (string) SystemSetting::get($prefix.'refresh_token', ''),
            'folder' => (string) SystemSetting::get($prefix.'folder', ''),
            'configured' => static::cloudIsConfigured($provider),
        ];
    }

    private static function tailwindColorHexMap(): array
    {
        return [
            'slate-400' => '#94a3b8', 'slate-500' => '#64748b', 'slate-600' => '#475569',
            'gray-400' => '#9ca3af', 'gray-500' => '#6b7280', 'gray-600' => '#4b5563',
            'zinc-400' => '#a1a1aa', 'zinc-500' => '#71717a', 'zinc-600' => '#52525b',
            'neutral-400' => '#a3a3a3', 'neutral-500' => '#737373', 'neutral-600' => '#525252',
            'stone-400' => '#a8a29e', 'stone-500' => '#78716c', 'stone-600' => '#57534e',
            'red-400' => '#f87171', 'red-500' => '#ef4444', 'red-600' => '#dc2626',
            'orange-400' => '#fb923c', 'orange-500' => '#f97316', 'orange-600' => '#ea580c',
            'amber-400' => '#fbbf24', 'amber-500' => '#f59e0b', 'amber-600' => '#d97706',
            'yellow-400' => '#facc15', 'yellow-500' => '#eab308', 'yellow-600' => '#ca8a04',
            'lime-400' => '#a3e635', 'lime-500' => '#84cc16', 'lime-600' => '#65a30d',
            'green-400' => '#4ade80', 'green-500' => '#22c55e', 'green-600' => '#16a34a',
            'emerald-400' => '#34d399', 'emerald-500' => '#10b981', 'emerald-600' => '#059669',
            'teal-400' => '#2dd4bf', 'teal-500' => '#14b8a6', 'teal-600' => '#0d9488',
            'cyan-400' => '#22d3ee', 'cyan-500' => '#06b6d4', 'cyan-600' => '#0891b2',
            'sky-400' => '#38bdf8', 'sky-500' => '#0ea5e9', 'sky-600' => '#0284c7',
            'blue-400' => '#60a5fa', 'blue-500' => '#3b82f6', 'blue-600' => '#2563eb',
            'indigo-400' => '#818cf8', 'indigo-500' => '#6366f1', 'indigo-600' => '#4f46e5',
            'violet-400' => '#a78bfa', 'violet-500' => '#8b5cf6', 'violet-600' => '#7c3aed',
            'purple-400' => '#c084fc', 'purple-500' => '#a855f7', 'purple-600' => '#9333ea',
            'fuchsia-400' => '#e879f9', 'fuchsia-500' => '#d946ef', 'fuchsia-600' => '#c026d3',
            'pink-400' => '#f472b6', 'pink-500' => '#ec4899', 'pink-600' => '#db2777',
            'rose-400' => '#fb7185', 'rose-500' => '#f43f5e', 'rose-600' => '#e11d48',
        ];
    }
}
