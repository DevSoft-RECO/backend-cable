<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Borrar sesión previa del mismo dispositivo
        $user->tokens()->where('name', $request->device_name)->delete();

        // Crear token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'message'      => 'Inicio de sesión exitoso',
            'token_type'   => 'Bearer',
            'access_token' => $token,
            'user'         => $this->formatUser($user),
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($token = $request->user()->currentAccessToken()) {
            $token->delete();
        }

        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }

    public function me(Request $request)
    {
        return response()->json($this->formatUser($request->user()));
    }

    // ─── Helper ────────────────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allPermissions = $isSuperAdmin
            ? \Spatie\Permission\Models\Permission::pluck('name')->toArray()
            : $user->getAllPermissions()->pluck('name')->toArray();

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->getRoleNames()->first() ?? 'Sin rol',
            'permissions' => array_values(array_unique($allPermissions)),
            'photo_url'   => $user->profile_photo_path
                              ? url('uploads/' . $user->profile_photo_path)
                              : null,
        ];
    }
}
