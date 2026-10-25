<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('outlet');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->status === 'active'
            );
        }

        $users = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = User::query()
            ->whereNotNull('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'outlets'));
    }

    public function create()
    {
        $roles = $this->availableRoles();
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('roles', 'outlets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:users,username',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'role' => [
                'required',
                Rule::in($this->availableRoles()),
            ],
            'outlet_id' => [
                Rule::requiredIf($request->input('role') === 'User Outlet'),
                'nullable',
                'integer',
                Rule::exists('outlets', 'id')->where('is_active', true),
                Rule::unique('users', 'outlet_id'),
            ],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'outlet_id' => $validated['role'] === 'User Outlet'
                ? $validated['outlet_id']
                : null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorizeManagedUser($user);
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $roles = $this->availableRoles();

        return view('users.edit', compact('user', 'roles', 'outlets'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManagedUser($user);

        abort_unless(
            $request->user()->role === 'Manager' || ! $request->filled('password'),
            403,
            'Hanya Manager yang dapat mengubah password pengguna.'
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                \Illuminate\Validation\Rule::unique('users', 'username')
                    ->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => [
                'required',
                \Illuminate\Validation\Rule::in($this->availableRoles()),
            ],
            'outlet_id' => [
                \Illuminate\Validation\Rule::requiredIf($request->input('role') === 'User Outlet'),
                'nullable',
                'integer',
                Rule::exists('outlets', 'id')->where('is_active', true),
                \Illuminate\Validation\Rule::unique('users', 'outlet_id')->ignore($user->id),
            ],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'] ?? null;
        $user->role = $validated['role'];
        $user->outlet_id = $validated['role'] === 'User Outlet'
            ? $validated['outlet_id']
            : null;

        if (! empty($validated['password'])) {
            $user->password = \Illuminate\Support\Facades\Hash::make(
                $validated['password']
            );
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeManagedUser($user);

        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                'Kamu tidak dapat menonaktifkan akun sendiri.'
            );
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with(
            'success',
            'Status pengguna berhasil diperbarui.'
        );
    }

    public function destroy(User $user)
    {
        $this->authorizeManagedUser($user);

        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                'Kamu tidak dapat menghapus akun yang sedang digunakan.'
            );
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    private function availableRoles(): array
    {
        $roles = [
            'Manager',
            'Admin Prolog',
            'Kepala Logistik',
            'User Outlet',
        ];

        return auth()->user()->role === 'Manager'
            ? $roles
            : ['User Outlet'];
    }

    private function authorizeManagedUser(User $user): void
    {
        abort_unless(auth()->user()->role === 'Manager', 403);
    }
}
