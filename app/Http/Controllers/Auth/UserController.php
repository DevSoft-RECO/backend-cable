<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    /**
     * GET /admin/usuarios
     * Lista todos los usuarios (excluyendo a Super Admin).
     */
    public function index()
    {
        $users = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))
            ->with('roles', 'permissions')
            ->get()
            ->map(fn($u) => $this->formatUser($u));

        return response()->json($users);
    }

    /**
     * GET /admin/usuarios/roles-permisos
     * Obtener el catálogo de roles permitidos y permisos disponibles.
     */
    public function getRolesAndPermissions()
    {
        $roles = Role::where('name', '!=', 'Super Admin')->pluck('name');
        $permissions = Permission::pluck('name');

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    /**
     * POST /admin/usuarios
     * Crear nuevo usuario con rol de la lista autorizada.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role'     => 'required|string|in:Dueño,Secretario(a),Cobrador',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        // El usuario nace sin permisos por defecto (se le asignan individualmente después)
        $user->syncPermissions($data['permissions'] ?? []);

        return response()->json($this->formatUser($user->load('roles', 'permissions')), 201);
    }

    /**
     * PUT /admin/usuarios/{id}
     * Actualizar datos, rol y permisos directos de un usuario.
     */
    public function update(Request $request, $id)
    {
        $user = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))->findOrFail($id);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|unique:users,email,{$id}",
            'password' => ['sometimes', 'nullable', Password::min(6)],
            'role'     => 'sometimes|string|in:Dueño,Secretario(a),Cobrador',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->fill(array_filter([
            'name'  => $data['name']  ?? null,
            'email' => $data['email'] ?? null,
        ]));

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if (isset($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }

        return response()->json($this->formatUser($user->load('roles', 'permissions')));
    }

    /**
     * DELETE /admin/usuarios/{id}
     * Eliminar un usuario (no puede eliminarse a sí mismo ni al Super Admin).
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin')) {
            return response()->json(['message' => 'No se puede eliminar la cuenta de Super Admin.'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * PUT /admin/usuarios/{id}/permisos
     * Sincronizar permisos de un usuario específico.
     */
    public function updatePermisos(Request $request, $id)
    {
        $user = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))->findOrFail($id);

        $data = $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->syncPermissions($data['permissions']);

        return response()->json([
            'message' => 'Permisos actualizados correctamente.',
            'user'    => $this->formatUser($user->load('roles', 'permissions')),
        ]);
    }

    /**
     * POST /profile/update
     * Actualiza el perfil del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$user->id}",
            'password' => ['nullable', 'confirmed', Password::min(6)],
            'photo'    => 'nullable|image|max:2048',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                $oldPath = public_path('uploads/' . $user->profile_photo_path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $file = $request->file('photo');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $path = public_path('uploads');
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $file->move($path, $filename);
            $user->profile_photo_path = $filename;
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user'    => $this->formatUser($user->load('roles', 'permissions')),
        ]);
    }

    // ─── Helper ────────────────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allPerms = $isSuperAdmin
            ? Permission::pluck('name')->toArray()
            : $user->getAllPermissions()->pluck('name')->toArray();

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->getRoleNames()->first() ?? 'Sin rol',
            'permissions' => array_values(array_unique($allPerms)),
            'photo_url'   => $user->profile_photo_path ? url('uploads/' . $user->profile_photo_path) : null,
        ];
    }
}
