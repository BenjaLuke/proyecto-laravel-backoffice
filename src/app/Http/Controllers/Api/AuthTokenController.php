<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Credenciales incorrectas.'],
            ]);
        }

        $user->tokens()->where('name', $data['device_name'])->delete();

        $token = $user->createToken($data['device_name'], $this->tokenAbilitiesFor($user));

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revocado correctamente.',
        ]);
    }

    private function tokenAbilitiesFor(User $user): array
    {
        $permissionsToAbilities = [
            'products_view' => 'products:read',
            'products_manage' => 'products:write',
            'categories_view' => 'categories:read',
            'categories_manage' => 'categories:write',
            'calendar_view' => 'calendar:read',
            'calendar_manage' => 'calendar:write',
        ];

        return collect($permissionsToAbilities)
            ->filter(fn (string $ability, string $permission): bool => $user->hasPermission($permission))
            ->values()
            ->all();
    }
}
