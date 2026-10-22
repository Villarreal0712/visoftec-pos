<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')
                     ->with('users')
                     ->orderBy('name')
                     ->get();

        $totalPermisos = Permission::count();

        return view('roles.index', compact('roles', 'totalPermisos'));
    }

    public function create()
    {
        $permisosPorModulo = $this->permisosPorModulo();
        $permisosActivos   = [];

        return view('roles.form', compact('permisosPorModulo', 'permisosActivos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:50|unique:roles,name',
            'permisos'   => 'nullable|array',
            'permisos.*' => 'exists:permissions,name',
        ]);

        $rol = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $rol->syncPermissions($request->permisos ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')
                         ->with('success', "Rol \"{$rol->name}\" creado correctamente.");
    }

    public function edit(Role $rol)
    {
        $rol->load('permissions');
        $permisosPorModulo = $this->permisosPorModulo();
        $permisosActivos   = $rol->permissions->pluck('name')->toArray();

        return view('roles.form', compact('rol', 'permisosPorModulo', 'permisosActivos'));
    }

    public function update(Request $request, Role $rol)
    {
        $request->validate([
            'name'       => "required|string|max:50|unique:roles,name,{$rol->id}",
            'permisos'   => 'nullable|array',
            'permisos.*' => 'exists:permissions,name',
        ]);

        $rol->update(['name' => $request->name]);
        $rol->syncPermissions($request->permisos ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')
                         ->with('success', "Rol \"{$rol->name}\" actualizado correctamente.");
    }

    public function destroy(Role $rol)
    {
        $total = $rol->users()->count();

        if ($total > 0) {
            return back()->with('error',
                "No se puede eliminar \"{$rol->name}\": tiene {$total} usuario(s) asignado(s)."
            );
        }

        $nombre = $rol->name;
        $rol->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')
                         ->with('success', "Rol \"{$nombre}\" eliminado correctamente.");
    }

    // ──────────────────────────────────────────
    // Helper: agrupa todos los permisos por módulo
    // ──────────────────────────────────────────
    private function permisosPorModulo(): array
    {
        $moduloLabels = [
            'productos'     => ['label' => 'Productos',       'icono' => 'tag'],
            'inventario'    => ['label' => 'Inventario',      'icono' => 'cube'],
            'clientes'      => ['label' => 'Clientes',        'icono' => 'users'],
            'ventas'        => ['label' => 'Ventas',          'icono' => 'shopping-cart'],
            'creditos'      => ['label' => 'Créditos',        'icono' => 'credit-card'],
            'gastos'        => ['label' => 'Gastos',          'icono' => 'cash'],
            'caja'          => ['label' => 'Cuadre de Caja',  'icono' => 'calculator'],
            'reportes'      => ['label' => 'Reportes',        'icono' => 'chart-bar'],
            'usuarios'      => ['label' => 'Usuarios',        'icono' => 'user-group'],
            'roles'         => ['label' => 'Roles',           'icono' => 'shield'],
            'configuracion' => ['label' => 'Configuración',   'icono' => 'cog'],
        ];

        $accionLabels = [
            'ver'        => 'Ver',
            'crear'      => 'Crear',
            'editar'     => 'Editar',
            'eliminar'   => 'Eliminar',
            'entradas'   => 'Entradas',
            'salidas'    => 'Salidas',
            'ajustes'    => 'Ajustes',
            'anular'     => 'Anular',
            'gestionar'  => 'Gestionar',
            'abrir'      => 'Abrir',
            'cerrar'     => 'Cerrar',
            'exportar'   => 'Exportar',
        ];

        $resultado = [];

        foreach (Permission::orderBy('name')->get() as $permiso) {
            [$modulo, $accion] = explode('.', $permiso->name, 2);

            if (!isset($resultado[$modulo])) {
                $resultado[$modulo] = [
                    'label'    => $moduloLabels[$modulo]['label'] ?? ucfirst($modulo),
                    'permisos' => [],
                ];
            }

            $resultado[$modulo]['permisos'][] = [
                'name'   => $permiso->name,
                'accion' => $accionLabels[$accion] ?? ucfirst($accion),
            ];
        }

        return $resultado;
    }
}
