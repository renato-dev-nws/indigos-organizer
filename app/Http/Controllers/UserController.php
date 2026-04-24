<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private function canManageUsers(?User $user): bool
    {
        return (bool) ($user?->is_admin || $user?->is_super_admin);
    }

    private function canManageTargetUser(User $authUser, User $targetUser): bool
    {
        if ($targetUser->is_super_admin && ! $authUser->is_super_admin) {
            return false;
        }

        return $this->canManageUsers($authUser) || (string) $authUser->id === (string) $targetUser->id;
    }

    public function index(): Response
    {
        $users = User::query()
            ->withCount(['ideas', 'contents', 'tasks', 'venues'])
            ->when(request('search'), fn ($q, $search) => $q->where(function ($qq) use ($search): void {
                $qq->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create(): Response
    {
        abort_unless($this->canManageUsers(Auth::user()), 403);

        return Inertia::render('Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        abort_unless($this->canManageUsers($authUser), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['sometimes', 'boolean'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'theme' => ['required', 'in:light,dark,system'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (! $authUser?->is_super_admin) {
            unset($validated['is_admin']);
        }

        $validated['is_super_admin'] = false;

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $user): Response
    {
        $authUser = Auth::user();
        abort_unless($authUser && $this->canManageTargetUser($authUser, $user), 403);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'notificationTypes' => User::notificationTypeOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = Auth::user();
        abort_unless($authUser && $this->canManageTargetUser($authUser, $user), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'is_admin' => ['sometimes', 'boolean'],
            'avatar_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (is_string($value) && str_starts_with($value, '/storage/')) {
                        return;
                    }

                    if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                        return;
                    }

                    $fail('O campo :attribute deve ser uma URL valida.');
                },
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
            'push_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['nullable', 'array'],
            'notification_preferences.*.push' => ['nullable', 'boolean'],
            'notification_preferences.*.email' => ['nullable', 'boolean'],
            'notification_preferences.*.whatsapp' => ['nullable', 'boolean'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'theme' => ['sometimes', 'in:light,dark,system'],
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_url'] = asset('storage/'.$path);
        }

        unset($validated['avatar']);

        if (! $authUser->is_super_admin) {
            unset($validated['is_admin']);
        }

        if ($user->is_super_admin) {
            $validated['is_admin'] = true;
        }

        unset($validated['is_super_admin']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('users.edit', $user)->with('success', 'Usuario atualizado com sucesso.');
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $authUser = Auth::user();
        abort_unless($authUser && $this->canManageTargetUser($authUser, $user), 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'Senha alterada com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $authUser = Auth::user();
        abort_unless($authUser && $this->canManageUsers($authUser), 403);
        abort_unless($this->canManageTargetUser($authUser, $user), 403);
        abort_if($user->is_super_admin, 422, 'Usuários super-admin não podem ser removidos.');
        abort_if((string) $user->getKey() === (string) Auth::id(), 422, 'Você não pode remover seu próprio usuário.');

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário removido com sucesso.');
    }
}
