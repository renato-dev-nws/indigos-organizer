<?php

namespace App\Notifications\Channels;

use App\Models\SystemSetting;
use App\Notifications\Contracts\ShouldSendWhatsApp;
use App\Services\EvolutionApiService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class WhatsAppChannel
{
    public function __construct(private readonly EvolutionApiService $whatsApp)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof ShouldSendWhatsApp) {
            return;
        }

        $payload = $notification->toWhatsApp($notifiable);
        $content = is_string($payload) ? $payload : Arr::get($payload, 'content');
        $instance = is_array($payload) ? Arr::get($payload, 'instance') : null;
        $groupKeys = is_array($payload) ? Arr::wrap(Arr::get($payload, 'groups', [])) : [];

        if (! is_string($content) || $content === '') {
            return;
        }

        $recipients = $this->resolveRecipients($notifiable, $groupKeys);

        foreach ($recipients as $recipient) {
            $this->whatsApp->sendTextMessage($recipient, $content, is_string($instance) ? $instance : null);
        }
    }

    private function resolveRecipients(object $notifiable, array $groupKeys): array
    {
        $resolved = [];

        $userId = (string) ($notifiable->id ?? '');
        if ($userId !== '') {
            // Prefer per-user whatsapp_phone field.
            $userPhone = trim((string) ($notifiable->whatsapp_phone ?? ''));
            if ($userPhone !== '') {
                $resolved[] = $userPhone;
            } else {
                // Backward compat: fall back to system-level route map.
                $userRouteMap = $this->parseRoutes((string) SystemSetting::get('evolution_whatsapp_user_routes', (string) config('services.evolution.user_routes', '')));
                if (isset($userRouteMap[$userId])) {
                    $resolved[] = $userRouteMap[$userId];
                }
            }
        }

        if ($groupKeys !== []) {
            $groupRouteMap = $this->parseRoutes((string) SystemSetting::get('evolution_whatsapp_group_routes', (string) config('services.evolution.group_routes', '')));

            foreach ($groupKeys as $groupKey) {
                if (! is_string($groupKey) || trim($groupKey) === '') {
                    continue;
                }

                $normalizedKey = trim($groupKey);
                $resolved[] = $groupRouteMap[$normalizedKey] ?? $normalizedKey;
            }
        }

        return array_values(array_unique(array_filter($resolved, fn ($value) => is_string($value) && $value !== '')));
    }

    private function parseRoutes(string $rawRoutes): array
    {
        $routes = [];

        foreach (preg_split('/\r\n|\r|\n/', $rawRoutes) ?: [] as $line) {
            $cleanLine = trim($line);
            if ($cleanLine === '' || str_starts_with($cleanLine, '#')) {
                continue;
            }

            if (! str_contains($cleanLine, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $cleanLine, 2));
            if ($key === '' || $value === '') {
                continue;
            }

            $routes[$key] = $value;
        }

        return $routes;
    }
}
