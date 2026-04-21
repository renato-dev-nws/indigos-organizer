<?php

namespace App\Http\Controllers;

use App\Models\UserCloudConnection;
use App\Support\CloudStorageManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class CloudConnectionController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, [UserCloudConnection::PROVIDER_GOOGLE, UserCloudConnection::PROVIDER_DROPBOX], true), 404);

        if ($provider === UserCloudConnection::PROVIDER_GOOGLE) {
            Config::set('services.google.redirect', route('cloud.callback', ['provider' => UserCloudConnection::PROVIDER_GOOGLE]));

            /** @var GoogleProvider $googleDriver */
            $googleDriver = Socialite::driver('google');

            return $googleDriver
                ->scopes(['https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
                ->redirect();
        }

        $state = csrf_token();
        session(['dropbox_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.dropbox.client_id'),
            'response_type' => 'code',
            'token_access_type' => 'offline',
            'redirect_uri' => URL::route('cloud.callback', ['provider' => UserCloudConnection::PROVIDER_DROPBOX]),
            'state' => $state,
        ]);

        return redirect()->away('https://www.dropbox.com/oauth2/authorize?'.$query);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, [UserCloudConnection::PROVIDER_GOOGLE, UserCloudConnection::PROVIDER_DROPBOX], true), 404);

        if ($provider === UserCloudConnection::PROVIDER_GOOGLE) {
            Config::set('services.google.redirect', route('cloud.callback', ['provider' => UserCloudConnection::PROVIDER_GOOGLE]));
            $googleUser = Socialite::driver('google')->user();
            $existing = $request->user()->cloudConnections()->where('provider', UserCloudConnection::PROVIDER_GOOGLE)->first();

            $request->user()->cloudConnections()->updateOrCreate(
                ['provider' => UserCloudConnection::PROVIDER_GOOGLE],
                [
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken ?: $existing?->refresh_token,
                    'token_expires_at' => now()->addSeconds((int) ($googleUser->expiresIn ?? 3600)),
                    'account_name' => $googleUser->name,
                    'account_email' => $googleUser->email,
                    'base_folder' => $existing?->base_folder ?: 'ERP_Arquivos',
                ]
            );

            return redirect()->route('settings.pages.system')->with('success', 'Google Drive conectado com sucesso.');
        }

        if ($request->string('state')->toString() !== session('dropbox_oauth_state')) {
            return redirect()->route('settings.pages.system')->with('error', 'Falha de validacao da conexao Dropbox.');
        }

        $tokenResponse = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
            'code' => $request->string('code')->toString(),
            'grant_type' => 'authorization_code',
            'client_id' => config('services.dropbox.client_id'),
            'client_secret' => config('services.dropbox.client_secret'),
            'redirect_uri' => URL::route('cloud.callback', ['provider' => UserCloudConnection::PROVIDER_DROPBOX]),
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()->route('settings.pages.system')->with('error', 'Nao foi possivel concluir a conexao com Dropbox.');
        }

        $tokenData = $tokenResponse->json();
        $accountResponse = Http::withToken($tokenData['access_token'])->post('https://api.dropboxapi.com/2/users/get_current_account');
        $accountData = $accountResponse->successful() ? $accountResponse->json() : [];

        $existing = $request->user()->cloudConnections()->where('provider', UserCloudConnection::PROVIDER_DROPBOX)->first();

        $request->user()->cloudConnections()->updateOrCreate(
            ['provider' => UserCloudConnection::PROVIDER_DROPBOX],
            [
                'access_token' => $tokenData['access_token'] ?? null,
                'refresh_token' => $tokenData['refresh_token'] ?? $existing?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : null,
                'account_name' => $accountData['name']['display_name'] ?? null,
                'account_email' => $accountData['email'] ?? null,
                'base_folder' => $existing?->base_folder ?: 'ERP_Arquivos',
            ]
        );

        return redirect()->route('settings.pages.system')->with('success', 'Dropbox conectado com sucesso.');
    }

    public function disconnect(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, [UserCloudConnection::PROVIDER_GOOGLE, UserCloudConnection::PROVIDER_DROPBOX], true), 404);

        $request->user()->cloudConnections()->where('provider', $provider)->delete();

        return back()->with('success', ucfirst($provider).' desconectado com sucesso.');
    }

    public function updateFolder(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, [UserCloudConnection::PROVIDER_GOOGLE, UserCloudConnection::PROVIDER_DROPBOX], true), 404);

        $payload = $request->validate([
            'base_folder' => ['required', 'string', 'max:255'],
        ]);

        $connection = $request->user()->cloudConnections()->where('provider', $provider)->first();
        if (! $connection) {
            return back()->with('error', 'Conecte o provedor antes de definir a pasta base.');
        }

        $connection->update(['base_folder' => trim($payload['base_folder'])]);

        return back()->with('success', 'Pasta base atualizada.');
    }

    public function test(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, [UserCloudConnection::PROVIDER_GOOGLE, UserCloudConnection::PROVIDER_DROPBOX], true), 404);

        $connection = $request->user()->cloudConnections()->where('provider', $provider)->first();
        if (! $connection || ! $connection->isConnected()) {
            return back()->with('error', 'Conecte o provedor antes de testar a integração.');
        }

        try {
            CloudStorageManager::testConnection($connection);

            return back()->with('success', ucfirst($provider).' conectado e respondendo normalmente.');
        } catch (\Throwable) {
            return back()->with('error', 'Falha ao testar a conexão com '.ucfirst($provider).'. Verifique o app OAuth, callbacks autorizados e a conta conectada.');
        }
    }

    public function browser(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, [UserCloudConnection::PROVIDER_GOOGLE, UserCloudConnection::PROVIDER_DROPBOX], true), 404);

        $connection = $request->user()->cloudConnections()->where('provider', $provider)->first();
        if (! $connection || ! $connection->isConnected()) {
            return response()->json(['message' => 'Provedor nao conectado.'], 422);
        }

        $path = $request->string('path')->toString();

        return response()->json([
            'nodes' => CloudStorageManager::listTreeNodes($connection, $path),
        ]);
    }
}
