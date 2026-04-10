<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateThemeRequest;
use Illuminate\Http\RedirectResponse;

class ThemeController extends Controller
{
    public function update(UpdateThemeRequest $request): RedirectResponse
    {
        $request->user()->update([
            'theme' => $request->theme,
        ]);

        return back()->with('success', 'Tema atualizado com sucesso.');
    }
}
