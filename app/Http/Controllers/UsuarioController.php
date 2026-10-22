<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Sucursal;
use App\Models\User;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%")
                  ->orWhere('usuario', 'like', "%{$request->buscar}%");
        }

        if ($request->filled('rol')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->rol));
        }

        if ($request->boolean('exportar')) {
            $items = (clone $query)->orderBy('nombre')->get();
            return $this->exportarCsv('usuarios', $items, [
                'ID'     => fn($u) => $u->id,
                'Nombre' => fn($u) => $u->nombre,
                'Usuario'=> fn($u) => $u->usuario,
                'Correo' => fn($u) => $u->email ?? '—',
                'Rol'    => fn($u) => $u->roles->pluck('name')->join(', ') ?: '—',
                'Activo' => fn($u) => $u->activo ? 'Sí' : 'No',
            ]);
        }

        $usuarios = $query->orderBy('nombre')->paginate($this->getPerPage())->withQueryString();
        $roles = Role::all();

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function create()
    {
        $roles      = Role::all();
        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();
        $bodegas    = Bodega::where('activo', true)->orderBy('nombre')->get();
        return view('usuarios.create', compact('roles', 'sucursales', 'bodegas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'usuario'     => 'required|string|max:50|unique:users,usuario',
            'email'       => 'nullable|email|max:100|unique:users,email',
            'password'    => ['required', Password::defaults(), 'confirmed'],
            'rol'         => 'required|exists:roles,name',
            'id_sucursal' => 'nullable|exists:sucursales,id',
            'id_bodega'   => 'nullable|exists:bodegas,id',
        ]);

        if (!auth()->user()->can('roles.gestionar')) {
            return back()->withInput()->with('error', 'No tienes permiso para asignar roles.');
        }

        $user = User::create([
            'nombre'      => $validated['nombre'],
            'usuario'     => $validated['usuario'],
            'email'       => $validated['email'] ?? null,
            'password'    => Hash::make($validated['password']),
            'activo'      => true,
            'id_sucursal' => $validated['id_sucursal'] ?? 1,
            'id_bodega'   => $validated['id_bodega'] ?? 1,
        ]);

        $user->assignRole($validated['rol']);

        return redirect()->route('usuarios.index')
                         ->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $usuario)
    {
        $usuario->load(['roles', 'ventas' => fn($q) => $q->with('cliente')->latest()->limit(10)]);
        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $usuario->load('roles');
        $roles      = Role::all();
        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();
        $bodegas    = Bodega::where('activo', true)->orderBy('nombre')->get();
        return view('usuarios.edit', compact('usuario', 'roles', 'sucursales', 'bodegas'));
    }

    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'usuario'     => "required|string|max:50|unique:users,usuario,{$usuario->id}",
            'email'       => "nullable|email|max:100|unique:users,email,{$usuario->id}",
            'rol'         => 'required|exists:roles,name',
            'activo'      => 'boolean',
            'id_sucursal' => 'nullable|exists:sucursales,id',
            'id_bodega'   => 'nullable|exists:bodegas,id',
        ]);

        if (!auth()->user()->can('roles.gestionar') && !$usuario->hasRole($validated['rol'])) {
            return back()->withInput()->with('error', 'No tienes permiso para cambiar el rol de este usuario.');
        }

        // Proteger al último Administrador activo
        if ($usuario->hasRole('Administrador')) {
            $otrosAdmins = User::whereHas('roles', fn($q) => $q->where('name', 'Administrador'))
                               ->where('id', '!=', $usuario->id)
                               ->where('activo', true)
                               ->exists();

            if (!$otrosAdmins) {
                if ($validated['rol'] !== 'Administrador') {
                    return back()->withInput()
                        ->with('error', 'No puedes cambiar el rol del único Administrador activo del sistema.');
                }
                if (!$request->boolean('activo', true)) {
                    return back()->withInput()
                        ->with('error', 'No puedes desactivar al único Administrador activo del sistema.');
                }
            }
        }

        $usuario->update([
            'nombre'      => $validated['nombre'],
            'usuario'     => $validated['usuario'],
            'email'       => $validated['email'] ?? null,
            'activo'      => $request->boolean('activo', true),
            'id_sucursal' => $validated['id_sucursal'] ?? $usuario->id_sucursal,
            'id_bodega'   => $validated['id_bodega'] ?? $usuario->id_bodega,
        ]);

        $usuario->syncRoles([$validated['rol']]);

        return redirect()->route('usuarios.show', $usuario)
                         ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($usuario->hasRole('Administrador')) {
            $otrosAdmins = User::whereHas('roles', fn($q) => $q->where('name', 'Administrador'))
                               ->where('id', '!=', $usuario->id)
                               ->where('activo', true)
                               ->exists();

            if (!$otrosAdmins) {
                return back()->with('error', 'No puedes eliminar al único Administrador activo del sistema.');
            }
        }

        if ($usuario->ventas()->exists()) {
            $usuario->update(['activo' => false]);
            return redirect()->route('usuarios.index')
                             ->with('info', 'Usuario desactivado (tiene ventas asociadas).');
        }

        $usuario->delete();
        return redirect()->route('usuarios.index')
                         ->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Cambia la contraseña de un usuario
     */
    public function cambiarPassword(Request $request, User $usuario)
    {
        if ($usuario->hasRole('Administrador') && !auth()->user()->can('roles.gestionar')) {
            return back()->with('error', 'No tienes permiso para cambiar la contraseña de un Administrador.');
        }

        $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $usuario->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    /**
     * Perfil del usuario logueado
     */
    public function perfil()
    {
        $usuario = auth()->user()->load('roles');
        return view('usuarios.perfil', compact('usuario'));
    }

    /**
     * Actualiza el perfil del usuario logueado
     */
    public function actualizarPerfil(Request $request)
    {
        $usuario = auth()->user();

        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => "nullable|email|max:100|unique:users,email,{$usuario->id}",
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('foto_perfil')) {
            if ($usuario->foto_perfil) {
                Storage::disk('public')->delete($usuario->foto_perfil);
            }
            $path = $request->file('foto_perfil')->store('usuarios', 'public');
            $usuario->foto_perfil = $path;
        }

        $usuario->nombre = $request->nombre;
        $usuario->email = $request->email;
        $usuario->save();

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
