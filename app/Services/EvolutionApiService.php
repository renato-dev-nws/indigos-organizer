<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EvolutionApiService
{
    public function sendTextMessage(string $recipient, string $message, ?string $instance = null): bool
    {
        $instanceName = $this->resolveInstanceName($instance);

        if ($recipient === '' || $message === '') {
            return false;
        }

        $result = $this->performJsonRequest('post', '/message/sendText/'.rawurlencode($instanceName), [
                'number' => $recipient,
                'text' => $message,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing',
                ],
            ]);

        if (! $result['ok']) {
            Log::warning('Evolution API returned an error while sending WhatsApp message.', [
                'status' => $result['status'],
                'recipient' => $recipient,
                'instance' => $instanceName,
                'body' => $result['data'],
            ]);
        }

        return $result['ok'];
    }

    public function fetchQrCode(?string $instance = null, ?string $number = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);
        $pairingNumber = $this->normalizePairingNumber($number);

        $state = $this->ensureInstanceExists($instanceName, $pairingNumber);
        if (! $state['ok']) {
            return $state;
        }

        $lastConnect = [
            'ok' => true,
            'status' => 200,
            'message' => '',
            'data' => [],
        ];

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $connect = $this->performJsonRequest('get', '/instance/connect/'.rawurlencode($instanceName), $pairingNumber !== null ? [
                'number' => $pairingNumber,
            ] : []);
            if (! $connect['ok']) {
                return $connect;
            }

            $lastConnect = $connect;
            if ($this->hasQrCodeData($connect['data'])) {
                return [
                    ...$connect,
                    'data' => $this->attachStateToPayload($connect['data'], $state['data']),
                ];
            }

            if ($attempt < 3) {
                usleep(750000);
            }
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => $pairingNumber !== null
                ? 'A instancia esta conectando. O QR visual ainda nao foi emitido; tente usar o codigo de pareamento quando disponivel.'
                : 'A instancia esta conectando. O QR Code ainda nao foi emitido.',
            'data' => $this->attachStateToPayload($lastConnect['data'], $state['data']),
        ];
    }

    public function fetchConnectionState(?string $instance = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);

        return $this->ensureInstanceExists($instanceName);
    }

    public function fetchInstanceInfo(?string $instance = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);

        $result = $this->performJsonRequest('get', '/instance/fetchInstances');
        if (! $result['ok']) {
            return $result;
        }

        $list = $result['data'];
        if (! is_array($list)) {
            return $result;
        }

        // fetchInstances returns an array of instance objects
        foreach ($list as $item) {
            if (is_array($item) && (string) ($item['name'] ?? '') === $instanceName) {
                return [
                    'ok' => true,
                    'status' => 200,
                    'message' => '',
                    'data' => $item,
                ];
            }
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => '',
            'data' => [],
        ];
    }

    public function disconnectInstance(?string $instance = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);
        $endpoint = '/instance/logout/'.rawurlencode($instanceName);

        $deleteResult = $this->performJsonRequest('delete', $endpoint);
        if ($deleteResult['ok']) {
            return $deleteResult;
        }

        $postResult = $this->performJsonRequest('post', $endpoint);
        if ($postResult['ok']) {
            return $postResult;
        }

        return [
            'ok' => false,
            'status' => $postResult['status'] > 0 ? $postResult['status'] : $deleteResult['status'],
            'message' => $postResult['message'] !== '' ? $postResult['message'] : $deleteResult['message'],
            'data' => $postResult['data'] !== [] ? $postResult['data'] : $deleteResult['data'],
        ];
    }

    public function reconnectInstance(?string $instance = null, ?string $number = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);
        $pairingNumber = $this->normalizePairingNumber($number);

        // Best effort cleanup of current runtime/session before recreating the instance.
        $this->disconnectInstance($instanceName);

        $deleted = $this->performJsonRequest('delete', '/instance/delete/'.rawurlencode($instanceName));
        if (! $deleted['ok'] && ! $this->isMissingInstanceError($deleted)) {
            Log::warning('Evolution API failed to delete instance during reconnect flow.', [
                'instance' => $instanceName,
                'status' => $deleted['status'],
                'body' => $deleted['data'],
            ]);
        }

        // Give Evolution API time to fully finalize the deletion before re-creating.
        // Without this delay, a POST /instance/create immediately after DELETE returns
        // 403 "already in use" due to a race condition in Evolution API's internal state.
        usleep(1000000); // 1 second

        $created = $this->performJsonRequest('post', '/instance/create', [
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        if (! $created['ok'] && ! $this->isAlreadyExistingInstanceError($created)) {
            return $created;
        }

        // Do not attempt to fetch QR inline — Baileys needs a few seconds to initialize
        // after instance creation. The caller should trigger a separate QR fetch request.
        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Instancia reiniciada. O QR Code sera gerado em breve.',
            'data' => [],
        ];
    }

    public function resolveInstanceName(?string $instance = null): string
    {
        $instanceName = trim((string) $instance);
        if ($instanceName !== '') {
            return $instanceName;
        }

        return trim((string) SystemSetting::get('evolution_instance', (string) config('services.evolution.instance')));
    }

    private function performJsonRequest(string $method, string $endpoint, array $payload = []): array
    {
        $baseUrl = rtrim((string) config('services.evolution.base_url'), '/');
        $apiKey = (string) config('services.evolution.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            Log::warning('Evolution API is not configured.', [
                'has_base_url' => $baseUrl !== '',
                'has_api_key' => $apiKey !== '',
            ]);

            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Evolution API nao configurada.',
                'data' => [],
            ];
        }

        $url = $baseUrl.$endpoint;

        try {
            $request = Http::timeout((int) config('services.evolution.timeout', 10))
                ->withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ]);

            $response = match (strtolower($method)) {
                'get' => $request->get($url, $payload),
                'post' => $request->post($url, $payload),
                'delete' => $request->delete($url, $payload),
                default => throw new \InvalidArgumentException('Unsupported HTTP method: '.$method),
            };

            $data = $response->json();
            if (! is_array($data)) {
                $data = ['raw' => $response->body()];
            }

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'message' => $this->extractResponseMessage($data),
                'data' => $data,
            ];
        } catch (Throwable $exception) {
            Log::warning('Evolution API request failed unexpectedly.', [
                'method' => $method,
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 500,
                'message' => 'Falha ao comunicar com a Evolution API.',
                'data' => [],
            ];
        }
    }

    private function ensureInstanceExists(string $instanceName, ?string $pairingNumber = null): array
    {
        $state = $this->performJsonRequest('get', '/instance/connectionState/'.rawurlencode($instanceName));
        if ($state['ok']) {
            return $state;
        }

        if (! $this->isMissingInstanceError($state)) {
            return $state;
        }

        $created = $this->performJsonRequest('post', '/instance/create', [
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        if (! $created['ok']) {
            return $created;
        }

        // After creation, state endpoint becomes available and should return "connecting".
        return $this->performJsonRequest('get', '/instance/connectionState/'.rawurlencode($instanceName));
    }

    private function hasQrCodeData(array $payload): bool
    {
        $base64 = $payload['base64'] ?? ($payload['qrcode']['base64'] ?? null);
        if (is_string($base64) && trim($base64) !== '') {
            return true;
        }

        $code = $payload['code'] ?? ($payload['qrcode']['code'] ?? null);
        if (is_string($code) && trim($code) !== '') {
            return true;
        }

        $pairingCode = $payload['pairingCode'] ?? null;

        return is_string($pairingCode) && trim($pairingCode) !== '';
    }

    private function attachStateToPayload(array $payload, array $statePayload): array
    {
        $state = $statePayload['instance']['state'] ?? null;
        if (! is_string($state) || trim($state) === '') {
            return $payload;
        }

        if (! isset($payload['instance']) || ! is_array($payload['instance'])) {
            $payload['instance'] = [];
        }

        $payload['instance']['state'] = $state;

        return $payload;
    }

    private function isMissingInstanceError(array $result): bool
    {
        if ((int) ($result['status'] ?? 0) !== 404) {
            return false;
        }

        $message = strtolower((string) ($result['message'] ?? ''));
        if (str_contains($message, 'does not exist') || str_contains($message, 'nao existe')) {
            return true;
        }

        $data = strtolower(json_encode($result['data'] ?? [], JSON_UNESCAPED_UNICODE) ?: '');

        return str_contains($data, 'does not exist') || str_contains($data, 'nao existe');
    }

    private function extractResponseMessage(array $data): string
    {
        // Evolution API v2 wraps the specific message in response.message (array or string).
        // Check that before falling back to the generic top-level error/message fields.
        $nested = $data['response']['message'] ?? null;
        if (is_array($nested) && ! empty($nested)) {
            $text = implode(', ', array_filter($nested, 'is_string'));
            if ($text !== '') {
                return $text;
            }
        }
        if (is_string($nested) && $nested !== '') {
            return $nested;
        }

        $msg = $data['message'] ?? $data['error'] ?? '';

        return is_string($msg) ? $msg : '';
    }

    private function isAlreadyExistingInstanceError(array $result): bool
    {
        $status = (int) ($result['status'] ?? 0);
        // Evolution API v2 returns 403 (not 400/409) when an instance name is already in use.
        if (! in_array($status, [400, 403, 409], true)) {
            return false;
        }

        $keywords = ['already exist', 'already exists', 'already in use', 'ja existe'];

        $message = strtolower((string) ($result['message'] ?? ''));
        foreach ($keywords as $kw) {
            if (str_contains($message, $kw)) {
                return true;
            }
        }

        // Also scan the full response body (handles nested response.message arrays).
        $data = strtolower(json_encode($result['data'] ?? [], JSON_UNESCAPED_UNICODE) ?: '');
        foreach ($keywords as $kw) {
            if (str_contains($data, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePairingNumber(?string $number): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $number);
        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        return $normalized;
    }
}
