<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /** Dominio del correo interno que se genera para usuarios sin correo real. */
    private const PLACEHOLDER_DOMAIN = '@miralto.local';

    public function index(): Response
    {
        $employees = Employee::withCount('orders')
            ->with('user:id,username,is_active')
            ->orderByRaw('is_active DESC')
            ->orderBy('name')
            ->get();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'roles' => Employee::ROLES,
            'stats' => [
                'total' => $employees->count(),
                'active' => $employees->where('is_active', true)->count(),
                'inactive' => $employees->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:employees,name'],
            'position' => ['nullable', 'string', 'max:100'],
            ...$this->roleAndAccessRules($request, null),
        ], $this->messages());

        DB::transaction(function () use ($validated) {
            $employee = Employee::create([
                'name' => $validated['name'],
                'position' => $validated['position'] ?? null,
                'role' => $validated['role'],
            ])->refresh(); // trae los defaults de BD (is_active)

            $this->syncUser($employee, $validated);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Empleado \"{$validated['name']}\" creado."]);

        return back();
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:employees,name,'.$employee->id],
            'position' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            ...$this->roleAndAccessRules($request, $employee),
        ], $this->messages());

        DB::transaction(function () use ($employee, $validated) {
            $employee->update([
                'name' => $validated['name'],
                'position' => $validated['position'] ?? null,
                'role' => $validated['role'],
                'is_active' => $validated['is_active'],
            ]);

            $this->syncUser($employee, $validated);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Empleado \"{$employee->name}\" actualizado."]);

        return back();
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        // Con historial de órdenes se desactiva en vez de eliminar (nullOnDelete perdería la asociación)
        if ($employee->orders()->exists()) {
            return back()->withErrors(['employee' => "\"{$employee->name}\" tiene órdenes asociadas; desactívalo en lugar de eliminarlo."]);
        }

        // orders.user_id es restrictOnDelete: un usuario con pedidos registrados no se puede borrar
        $user = $employee->user;
        if ($user && $user->orders()->exists()) {
            return back()->withErrors(['employee' => "\"{$employee->name}\" registró pedidos con su usuario; desactívalo en lugar de eliminarlo."]);
        }

        $name = $employee->name;

        DB::transaction(function () use ($employee, $user) {
            $employee->delete();
            $user?->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Empleado \"{$name}\" eliminado."]);

        return back();
    }

    /**
     * Reglas de rol y acceso (usuario + clave). Solo Mesero y Cocinero admiten acceso.
     *
     * @return array<string, mixed>
     */
    private function roleAndAccessRules(Request $request, ?Employee $employee): array
    {
        $wantsAccess = $request->boolean('has_access');
        $existingUser = $employee?->user;

        return [
            'role' => ['required', Rule::in(array_keys(Employee::ROLES))],
            'has_access' => ['required', 'boolean', function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                if ($value && ! in_array($request->input('role'), Employee::LOGIN_ROLES, true)) {
                    $fail('Solo los empleados con rol Mesero o Cocinero pueden tener acceso al sistema.');
                }
            }],
            'username' => [
                Rule::requiredIf($wantsAccess),
                'nullable', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($existingUser?->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($existingUser) {
                    // El correo interno se deriva del usuario; no puede chocar con otro correo existente
                    if ($value && User::where('email', strtolower($value).self::PLACEHOLDER_DOMAIN)
                        ->when($existingUser, fn ($q) => $q->whereKeyNot($existingUser->id))
                        ->exists()) {
                        $fail('Ese usuario ya está en uso.');
                    }
                },
            ],
            // Al crear el acceso la clave es obligatoria; al editar, vacía = no cambiarla
            'password' => [
                Rule::requiredIf($wantsAccess && ! $existingUser),
                'nullable', 'string', Password::default(),
            ],
        ];
    }

    /**
     * Crea, actualiza o desactiva el usuario del empleado según el switch de acceso.
     * Nunca se borra el usuario aquí: orders.user_id lo referencia.
     *
     * @param  array<string, mixed>  $v
     */
    private function syncUser(Employee $employee, array $v): void
    {
        $user = $employee->user;

        if (! $v['has_access']) {
            $user?->update(['is_active' => false]);

            return;
        }

        $username = strtolower($v['username']);
        $attributes = [
            'name' => $employee->name,
            'role' => $employee->role,
            'username' => $username,
            'is_active' => $employee->is_active,
        ];

        if (! empty($v['password'])) {
            $attributes['password'] = $v['password'];
        }

        if ($user) {
            // Solo se re-deriva el correo si era el interno; uno real no se toca
            if (str_ends_with($user->email, self::PLACEHOLDER_DOMAIN)) {
                $attributes['email'] = $username.self::PLACEHOLDER_DOMAIN;
            }
            $user->update($attributes);

            return;
        }

        $user = User::create([...$attributes, 'email' => $username.self::PLACEHOLDER_DOMAIN]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $employee->update(['user_id' => $user->id]);
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre del empleado es obligatorio.',
            'name.unique' => 'Ya existe un empleado con ese nombre.',
            'role.required' => 'Selecciona el rol del empleado.',
            'username.required' => 'El usuario es obligatorio para dar acceso.',
            'username.unique' => 'Ese usuario ya está en uso.',
            'username.regex' => 'El usuario solo admite letras, números, punto, guion y guion bajo.',
            'username.min' => 'El usuario debe tener al menos 3 caracteres.',
            'password.required' => 'La clave es obligatoria para crear el acceso.',
        ];
    }
}
