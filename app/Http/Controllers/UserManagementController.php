<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

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

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = $this->availableRoles();

        return view('users.create', compact('roles'));
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
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    
public function edit(User $user)
{
    $roles = $this->availableRoles();

    return view('users.edit', compact('user', 'roles'));
}

public function update(Request $request, User $user)
{
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
    ]);

    $user->name = $validated['name'];
    $user->username = $validated['username'];
    $user->email = $validated['email'] ?? null;
    $user->role = $validated['role'];

    if (!empty($validated['password'])) {
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
        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                'Kamu tidak dapat menonaktifkan akun sendiri.'
            );
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return back()->with(
            'success',
            'Status pengguna berhasil diperbarui.'
        );
    }

    public function destroy(User $user)
    {
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
        return [
            'Manager',
            'Admin Prolog',
            'Kepala Logistik',
        ];
    }

    
}
