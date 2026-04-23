<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\EvolutionApiService;
use App\Support\CloudStorageManager;
use App\Support\SystemSettingsRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function index(): Response
    {
        $logoPath = SystemSetting::get('logo_path');
        $iconPath = SystemSetting::get('icon_path');

        return Inertia::render('Settings/System', [
            'logoUrl' => $logoPath ? Storage::url($logoPath) : null,
            'iconUrl' => $iconPath ? Storage::url($iconPath) : null,
            'moduleDefinitions' => SystemSettingsRegistry::moduleDefinitions(),
            'moduleColors' => SystemSettingsRegistry::moduleColors(),
            'colorPalette' => SystemSettingsRegistry::tailwindColorPalette(),
            'cloudIntegrations' => [
                'google' => CloudStorageManager::integrationStatus('google'),
                'dropbox' => CloudStorageManager::integrationStatus('dropbox'),
            ],
            'whatsAppSettings' => [
                'instance' => (string) SystemSetting::get('evolution_instance', (string) config('services.evolution.instance', 'main')),
                'pair_number' => (string) SystemSetting::get('evolution_pairing_number', (string) config('services.evolution.pairing_number', '')),
                'group_routes' => (string) SystemSetting::get('evolution_whatsapp_group_routes', (string) config('services.evolution.group_routes', '')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->has('module_colors')) {
            $request->validate([
                'module_colors' => ['array'],
                'module_colors.*' => ['required', 'string', 'in:'.implode(',', SystemSettingsRegistry::tailwindColorTokens())],
            ]);

            foreach ($request->input('module_colors', []) as $moduleKey => $token) {
                SystemSetting::set("module_color_{$moduleKey}", $token);
            }
        }

        if ($request->hasFile('logo')) {
            $request->validate(['logo' => ['image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048']]);
            $old = SystemSetting::get('logo_path');
            if ($old) Storage::disk('public')->delete($old);
            $ext = $request->file('logo')->extension();
            $path = $request->file('logo')->storeAs('system', "logo.{$ext}", 'public');
            SystemSetting::set('logo_path', $path);
        }

        if ($request->hasFile('icon')) {
            $request->validate(['icon' => ['image', 'mimes:jpeg,png,gif,svg,webp,ico', 'max:512']]);
            $old = SystemSetting::get('icon_path');
            if ($old) Storage::disk('public')->delete($old);
            $ext = $request->file('icon')->extension();
            $path = $request->file('icon')->storeAs('system', "icon.{$ext}", 'public');
            SystemSetting::set('icon_path', $path);
        }

        if ($request->boolean('remove_logo')) {
            $old = SystemSetting::get('logo_path');
            if ($old) Storage::disk('public')->delete($old);
            SystemSetting::set('logo_path', null);
        }

        if ($request->boolean('remove_icon')) {
            $old = SystemSetting::get('icon_path');
            if ($old) Storage::disk('public')->delete($old);
            SystemSetting::set('icon_path', null);
        }

        if ($request->hasAny(['evolution_instance', 'evolution_pairing_number', 'evolution_whatsapp_group_routes'])) {
            $request->validate([
                'evolution_instance' => ['nullable', 'string', 'max:120'],
                'evolution_pairing_number' => ['nullable', 'string', 'max:30'],
                'evolution_whatsapp_group_routes' => ['nullable', 'string', 'max:10000'],
            ]);

            SystemSetting::set('evolution_instance', (string) $request->input('evolution_instance', ''));
            SystemSetting::set('evolution_pairing_number', (string) $request->input('evolution_pairing_number', ''));
            SystemSetting::set('evolution_whatsapp_group_routes', (string) $request->input('evolution_whatsapp_group_routes', ''));
        }

        return back()->with('success', 'Configurações do sistema salvas.');
    }

    public function whatsappQr(Request $request, EvolutionApiService $evolution): JsonResponse
    {
        $instance = $this->resolveRequestedInstance($request);
        $pairingNumber = $this->resolveRequestedPairingNumber($request);
        $result = $evolution->fetchQrCode($instance, $pairingNumber);

        if (! $result['ok']) {
            return $this->failedEvolutionResponse($result, 'Nao foi possivel gerar o QR Code.');
        }

        return response()->json([
            'success' => true,
            'instance' => $evolution->resolveInstanceName($instance),
            'data' => $this->normalizeQrPayload($result['data']),
        ]);
    }

    public function whatsappStatus(Request $request, EvolutionApiService $evolution): JsonResponse
    {
        $instance = $this->resolveRequestedInstance($request);
        $result = $evolution->fetchConnectionState($instance);

        if (! $result['ok']) {
            return $this->failedEvolutionResponse($result, 'Nao foi possivel consultar o status da instancia.');
        }

        $state = $this->extractConnectionState($result['data']) ?? 'unknown';

        // When connected, also fetch owner info (ownerJid, profileName) from fetchInstances.
        $ownerJid = null;
        $profileName = null;
        if (in_array($state, ['open', 'connected'], true)) {
            $info = $evolution->fetchInstanceInfo($instance);
            if ($info['ok'] && ! empty($info['data'])) {
                $ownerJid = $info['data']['ownerJid'] ?? null;
                $profileName = $info['data']['profileName'] ?? null;
            }
        }

        return response()->json([
            'success' => true,
            'instance' => $evolution->resolveInstanceName($instance),
            'data' => [
                'state' => $state,
                'ownerJid' => $ownerJid,
                'profileName' => $profileName,
                'raw' => $result['data'],
            ],
        ]);
    }

    public function whatsappSendTest(Request $request, EvolutionApiService $evolution): JsonResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:4096'],
            'instance' => ['nullable', 'string', 'max:120'],
        ]);

        $number = preg_replace('/\D+/', '', $validated['number']);
        if (! $number) {
            return response()->json(['success' => false, 'message' => 'Numero invalido.'], 422);
        }

        $instance = trim((string) ($validated['instance'] ?? ''));
        $sent = $evolution->sendTextMessage($number, $validated['message'], $instance !== '' ? $instance : null);

        if (! $sent) {
            return response()->json(['success' => false, 'message' => 'Falha ao enviar mensagem de teste. Verifique se a instancia esta conectada.'], 502);
        }

        return response()->json(['success' => true, 'message' => 'Mensagem enviada com sucesso.']);
    }

    public function whatsappDisconnect(Request $request, EvolutionApiService $evolution): JsonResponse
    {
        $instance = $this->resolveRequestedInstance($request);
        $result = $evolution->disconnectInstance($instance);

        if (! $result['ok']) {
            return $this->failedEvolutionResponse($result, 'Nao foi possivel desconectar a instancia.');
        }

        return response()->json([
            'success' => true,
            'instance' => $evolution->resolveInstanceName($instance),
            'message' => 'Instancia desconectada com sucesso.',
            'data' => $result['data'],
        ]);
    }

    public function whatsappReconnect(Request $request, EvolutionApiService $evolution): JsonResponse
    {
        $instance = $this->resolveRequestedInstance($request);
        // Reconnect always recreates in QR mode; pairing number is only used during QR fetch.
        $result = $evolution->reconnectInstance($instance, null);

        if (! $result['ok']) {
            return $this->failedEvolutionResponse($result, 'Nao foi possivel reiniciar a conexao da instancia.');
        }

        return response()->json([
            'success' => true,
            'instance' => $evolution->resolveInstanceName($instance),
            'message' => (string) (($result['message'] ?? '') !== '' ? $result['message'] : 'Instancia reiniciada com sucesso.'),
            'data' => $this->normalizeQrPayload($result['data']),
        ]);
    }

    private function resolveRequestedInstance(Request $request): ?string
    {
        $validated = $request->validate([
            'instance' => ['nullable', 'string', 'max:120'],
        ]);

        $instance = trim((string) ($validated['instance'] ?? ''));

        return $instance !== '' ? $instance : null;
    }

    private function resolveRequestedPairingNumber(Request $request): ?string
    {
        $validated = $request->validate([
            'number' => ['nullable', 'string', 'max:30'],
        ]);

        $raw = trim((string) ($validated['number'] ?? ''));

        $normalized = preg_replace('/\D+/', '', $raw);

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    private function failedEvolutionResponse(array $result, string $fallbackMessage): JsonResponse
    {
        $upstreamStatus = (int) ($result['status'] ?? 500);
        $status = match (true) {
            $upstreamStatus >= 500 => 502,
            $upstreamStatus >= 400 => 422,
            default => 500,
        };

        return response()->json([
            'success' => false,
            'message' => (string) (($result['message'] ?? '') !== '' ? $result['message'] : $fallbackMessage),
            'upstream_status' => $upstreamStatus,
            'data' => $result['data'] ?? [],
        ], $status);
    }

    private function normalizeQrPayload(array $data): array
    {
        $base64 = Arr::get($data, 'base64');
        if (! is_string($base64) || $base64 === '') {
            $base64 = (string) Arr::get($data, 'qrcode.base64', '');
        }

        if ($base64 !== '' && ! str_starts_with($base64, 'data:image')) {
            $base64 = 'data:image/png;base64,'.$base64;
        }

        $pairingCode = $this->normalizePairingCodeCandidate(
            Arr::get($data, 'pairingCode')
            ?? Arr::get($data, 'pairing.code')
            ?? Arr::get($data, 'response.pairingCode')
            ?? Arr::get($data, 'response.pairing.code')
            ?? Arr::get($data, 'code')
        );

        return [
            'code' => Arr::get($data, 'code'),
            'base64' => $base64 !== '' ? $base64 : null,
            'pairingCode' => $pairingCode,
            'connectionState' => $this->extractConnectionState($data) ?? 'unknown',
        ];
    }

    private function normalizePairingCodeCandidate(mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $clean = strtoupper(trim($candidate));
        if ($clean === '') {
            return null;
        }

        if (preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $clean) === 1) {
            return $clean;
        }

        if (preg_match('/^[A-Z0-9]{8}$/', $clean) === 1) {
            return substr($clean, 0, 4).'-'.substr($clean, 4, 4);
        }

        return null;
    }

    private function extractConnectionState(array $payload): ?string
    {
        $candidates = [
            Arr::get($payload, 'state'),
            Arr::get($payload, 'status'),
            Arr::get($payload, 'connectionStatus'),
            Arr::get($payload, 'instance.state'),
            Arr::get($payload, 'instance.status'),
            Arr::get($payload, 'instance.connectionStatus'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = trim($candidate);
            if ($normalized !== '') {
                return strtolower($normalized);
            }
        }

        return null;
    }
}
