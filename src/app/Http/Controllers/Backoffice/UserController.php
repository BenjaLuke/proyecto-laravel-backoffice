<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')
            ->orderBy('username')
            ->paginate(15);

        return view('backoffice.users.index', compact('users'));
    }

    public function create(): View
    {
        $user = new User([
            'is_admin' => false,
            'permissions' => User::defaultPermissions(),
        ]);

        return view('backoffice.users.create', [
            'user' => $user,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'is_admin' => $data['is_admin'],
            'permissions' => $data['permissions'],
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('backoffice.users.edit', [
            'user' => $user,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

        if (
            auth()->id() === $user->id &&
            !$data['is_admin'] &&
            empty($data['permissions']['users_manage'])
        ) {
            return back()
                ->withInput()
                ->with('error', 'No puedes quitarte a ti mismo el acceso a la gestión de usuarios.');
        }

        if (
            $user->is_admin &&
            !$data['is_admin'] &&
            User::where('is_admin', true)->count() <= 1
        ) {
            return back()
                ->withInput()
                ->with('error', 'Debe existir al menos un usuario administrador.');
        }

        $payload = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'is_admin' => $data['is_admin'],
            'permissions' => $data['permissions'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'No puedes borrarte a ti mismo.');
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'No puedes borrar el último administrador del sistema.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'is_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'username.required' => 'El nombre de usuario es obligatorio.',
                'username.unique' => 'Ese nombre de usuario ya existe.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico no tiene un formato válido.',
                'email.unique' => 'Ese correo electrónico ya existe.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            ]);

        $selectedPermissions = collect($request->input('permissions', []))
            ->mapWithKeys(fn ($permission) => [$permission => true])
            ->all();

        $permissions = array_replace(
            User::defaultPermissions(),
            $selectedPermissions
        );

        return [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'is_admin' => $request->boolean('is_admin'),
            'permissions' => $permissions,
            'password' => $validated['password'] ?? null,
        ];
    }

    private function permissionGroups(): array
    {
        return [
            'Categorías' => [
                'categories_view' => 'Ver categorías',
                'categories_manage' => 'Crear y editar categorías',
                'categories_delete' => 'Borrar categorías',
            ],
            'Productos' => [
                'products_view' => 'Ver productos',
                'products_manage' => 'Crear y editar productos',
                'products_delete' => 'Borrar y restaurar productos',
            ],
            'Calendario' => [
                'calendar_view' => 'Ver calendario',
                'calendar_manage' => 'Crear y editar pedidos',
                'calendar_delete' => 'Borrar pedidos',
            ],
            'Actividad y usuarios' => [
                'activity_view' => 'Ver actividad',
                'users_manage' => 'Gestionar usuarios',
            ],
        ];
    }
}