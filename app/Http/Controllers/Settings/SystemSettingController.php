<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserCloudConnection;
use App\Models\SystemSetting;
use App\Support\SystemSettingsRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        $logoPath = SystemSetting::get('logo_path');
        $iconPath = SystemSetting::get('icon_path');

        $cloudConnections = $user?->cloudConnections
            ?->keyBy('provider')
            ->map(fn (UserCloudConnection $connection) => [
                'configured' => $connection->isConnected(),
                'account_name' => $connection->account_name,
                'account_email' => $connection->account_email,
                'base_folder' => $connection->base_folder,
            ])
            ->toArray() ?? [];

        return Inertia::render('Settings/System', [
            'logoUrl' => $logoPath ? Storage::url($logoPath) : null,
            'iconUrl' => $iconPath ? Storage::url($iconPath) : null,
            'moduleDefinitions' => SystemSettingsRegistry::moduleDefinitions(),
            'moduleColors' => SystemSettingsRegistry::moduleColors(),
            'colorPalette' => SystemSettingsRegistry::tailwindColorPalette(),
            'cloudIntegrations' => [
                'google' => $cloudConnections['google'] ?? ['configured' => false, 'base_folder' => 'ERP_Arquivos'],
                'dropbox' => $cloudConnections['dropbox'] ?? ['configured' => false, 'base_folder' => 'ERP_Arquivos'],
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

        return back()->with('success', 'Configurações do sistema salvas.');
    }
}
