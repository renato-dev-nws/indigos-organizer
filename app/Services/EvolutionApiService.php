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
                'textMessage' => [
                    'text' => $message,
                ],
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

    public function fetchQrCode(?string $instance = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);

        $state = $this->ensureInstanceExists($instanceName);
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
            $connect = $this->performJsonRequest('get', '/instance/connect/'.rawurlencode($instanceName));
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
            'message' => 'A instancia esta conectando. O QR Code ainda nao foi emitido.',
            'data' => $this->attachStateToPayload($lastConnect['data'], $state['data']),
        ];
    }

    public function fetchConnectionState(?string $instance = null): array
    {
        $instanceName = $this->resolveInstanceName($instance);

        return $this->ensureInstanceExists($instanceName);
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
                'message' => (string) ($data['message'] ?? $data['error'] ?? ''),
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

    private function ensureInstanceExists(string $instanceName): array
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

        return is_string($code) && trim($code) !== '';
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
}
